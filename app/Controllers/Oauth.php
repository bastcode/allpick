<?php

namespace App\Controllers;

use App\Models\OauthModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RequestInterface;

use CoderCat\JWKToPEM\JWKConverter;
use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;


// enum Status: int
// {
//     case FB = 1;
//     case GG = 2;
//     case AP = 3;
// }


/**
 * @OA\Tag(
 *   name="Oauth",
 *     description="auth Api List [로그인 회원가입]"
 * )
 */
class Oauth extends BaseController
{
    protected $callbackurl = "/oauth/callback?type=";
    protected $callbackurl2 = "/oauth/callback/";

    public function __construct()
    {
    }


    private function return_url($type)
    {
        return base_url() . $this->callbackurl . $type;
    }

    private function return_url2($mode)
    {
        return base_url() . $this->callbackurl . $mode;
    }

    private function return_sns_type($type)
    {
        /*
        TWITTER_CODE =TW
        FACEBOOK_CODE=FB
        GOOGLE_CODE=GG
        NAVER_CODE=NV
        DAUM_CODE=DM
        YAHOO_CODE=YH
        APPLE_CODE=AP
        Status::tryFrom($type);
        */
        if ('FB' == $type) {
            return  1;
        } else if ('GG' == $type) {
            return 2;
        } else if ('AP' == $type) {
            return 3;
        }
    }


    // for twitter auth util
    private function buildBaseString($baseURI, $params)
    {

        $r = array();
        ksort($params);
        foreach ($params as $key => $value) {
            $r[] = "$key=" . rawurlencode($value);
        } //end foreach

        return "POST&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }


    // for twitter auth.
    private function getCompositeKey($consumerSecret, $requestToken)
    {
        return rawurlencode($consumerSecret) . '&' . rawurlencode($requestToken);
    }

    // for twitter auth.
    private function buildAuthorizationHeader($oauth)
    {
        $r = 'Authorization: OAuth ';

        $values = array();
        foreach ($oauth as $key => $value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";

        $r .= implode(', ', $values);
        return $r;
    }

    function encode($data)
    {
        $encoded = strtr(base64_encode($data), '+/', '-_');
        return rtrim($encoded, '=');
    }

    function generateJWT($kid, $iss, $sub)
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $kid
        ];
        $body = [
            'iss' => $iss,
            'iat' => time(),
            'exp' => time() + 3600,
            'aud' => 'https://appleid.apple.com',
            'sub' => $sub
        ];

        $privKey = openssl_pkey_get_private(file_get_contents(FCPATH . '../AuthKey_U342Q4WRK9.pem'));

        if (!$privKey) {
            return false;
        }

        $payload = $this->encode(json_encode($header)) . '.' . $this->encode(json_encode($body));

        $signature = '';
        $success = openssl_sign($payload, $signature, $privKey, OPENSSL_ALGO_SHA256);
        if (!$success) return false;

        $raw_signature = $this->fromDER($signature, 64);

        return $payload . '.' . $this->encode($raw_signature);
    }

    /**
     * @param string $der
     * @param int    $partLength
     *
     * @return string
     */
    function fromDER(string $der, int $partLength)
    {
        $hex = unpack('H*', $der)[1];
        if ('30' !== mb_substr($hex, 0, 2, '8bit')) { // SEQUENCE
            throw new \RuntimeException();
        }
        if ('81' === mb_substr($hex, 2, 2, '8bit')) { // LENGTH > 128
            $hex = mb_substr($hex, 6, null, '8bit');
        } else {
            $hex = mb_substr($hex, 4, null, '8bit');
        }
        if ('02' !== mb_substr($hex, 0, 2, '8bit')) { // INTEGER
            throw new \RuntimeException();
        }
        $Rl = hexdec(mb_substr($hex, 2, 2, '8bit'));
        $R = $this->retrievePositiveInteger(mb_substr($hex, 4, $Rl * 2, '8bit'));
        $R = str_pad($R, $partLength, '0', STR_PAD_LEFT);
        $hex = mb_substr($hex, 4 + $Rl * 2, null, '8bit');
        if ('02' !== mb_substr($hex, 0, 2, '8bit')) { // INTEGER
            throw new \RuntimeException();
        }
        $Sl = hexdec(mb_substr($hex, 2, 2, '8bit'));
        $S = $this->retrievePositiveInteger(mb_substr($hex, 4, $Sl * 2, '8bit'));
        $S = str_pad($S, $partLength, '0', STR_PAD_LEFT);
        return pack('H*', $R . $S);
    }
    /**
     * @param string $data
     *
     * @return string
     */
    function preparePositiveInteger(string $data)
    {
        if (mb_substr($data, 0, 2, '8bit') > '7f') {
            return '00' . $data;
        }
        while ('00' === mb_substr($data, 0, 2, '8bit') && mb_substr($data, 2, 2, '8bit') <= '7f') {
            $data = mb_substr($data, 2, null, '8bit');
        }
        return $data;
    }
    /**
     * @param string $data
     *
     * @return string
     */
    function retrievePositiveInteger(string $data)
    {
        while ('00' === mb_substr($data, 0, 2, '8bit') && mb_substr($data, 2, 2, '8bit') > '7f') {
            $data = mb_substr($data, 2, null, '8bit');
        }
        return $data;
    }


    /**
     *  @OA\Get(
     *      tags={"Oauth"},
     *      path="/oauth/sns_request_api",
     *      summary="SNS 로그인 요청 (FB, GG, AP) 리턴 > 주소 ",
     *      @OA\Response(
     *          response=200,
     *          description="sns oauth start"
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *  @OA\Parameter(
     *   parameter="type",
     *   name="type",
     *   @OA\Schema(
     *     type="string",     
     *     default="GG"
     *   ),
     *   in="query",
     *   required=false
     *   ),
     * )
     */
    public function sns_request_api()
    {
        $rules = [
            'type' => 'required',
        ];

        $input = $this->getRequestInputGetType($this->request);
        $mt = microtime();
        $rand = mt_rand();
        $state = md5($mt . $rand);

        if (!$this->validateRequest($input, $rules)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    200
                );
        }

        $type = $input['type'];

        if ($type == getenv('TWITTER_CODE')) {
            $url = "https://twitter.com/oauth/request_token";
            $timestamp = time();
            $oauth = array(
                'oauth_callback' => $this->return_url($type),
                'oauth_consumer_key' => getenv('TW_CLIENT_ID'),
                'oauth_nonce' => $timestamp,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $timestamp,
                'oauth_version' => '1.0'
            );
            $baseString = $this->buildBaseString($url, $oauth);

            $compositeKey = $this->getCompositeKey(getenv('TW_CLIENT_SECRET'), null);
            $oauth_signature = base64_encode(hash_hmac('sha1', $baseString, $compositeKey, true));

            $oauth['oauth_signature'] = $oauth_signature;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);

            $header = $this->buildAuthorizationHeader($oauth);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
            $result = curl_exec($ch);

            $url = "https://api.twitter.com/oauth/authorize?" . $result;
            curl_close($ch);

            $this->getResponseSend(['code' => 200, 'type' => 'TWITTER_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        } else if ($type == getenv('FACEBOOK_CODE')) {
            $state = bin2hex(openssl_random_pseudo_bytes(16));
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
            } else {
                $url = "https://www.facebook.com/v13.0/dialog/oauth?client_id=" . getenv('FB_CLIENT_ID') . "&redirect_uri=" . urlencode('https://api./oauth/callback/FB') . "&scope=public_profile,email&state=$state";
                $this->getResponseSend(['code' => 200, 'type' => 'FACEBOOK_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
            }
        } else if ($type == getenv('APPLE_CODE')) {
            $bundleId = "com.service.wevitt";
            $url = "https://appleid.apple.com/auth/authorize?response_type=code&responsemode=query&client_id=" . $bundleId . "&scope=name+email&state=" . $state . "&response_mode=form_post&redirect_uri=" . urlencode('https://api./oauth/callback/AP');
            // log_message('info', $url);
            $this->getResponseSend(['code' => 200, 'type' => 'APPLE_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        } else if ($type == getenv('GOOGLE_CODE')) {
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                //local
                $url = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&redirect_uri=" . urlencode('http://localhost/oauth/callback/GG') . "&response_type=code&prompt=consent&client_id=" . getenv('GG_LOCAL_CLIENT_ID');
            } else if ($_SERVER['SERVER_NAME'] == 'dev1api.') {
                //dev
                $url = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&redirect_uri=" . urlencode('https://dev1api./oauth/callback/GG') . "&response_type=code&prompt=consent&client_id=" . getenv('GG_DEV_CLIENT_ID');
            } else {
                //live
                $url = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&redirect_uri=" . urlencode('https://api./oauth/callback/GG') . "&response_type=code&prompt=consent&client_id=" . getenv('GG_CLIENT_ID');
            }
            $this->getResponseSend(['code' => 200, 'type' => 'GOOGLE_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        } else if ($type == getenv('KAKAO_CODE')) {
            $url = "https://kauth.kakao.com/oauth/authorize?client_id=" . getenv('KK_CLIENT_ID') . "&redirect_uri=" . urlencode($this->return_url($type)) . "&response_type=code";

            $this->getResponseSend(['code' => 200, 'type' => 'KAKAO_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        } else if ($type == getenv('NAVER_CODE')) {
            $url = "https://nid.naver.com/oauth2.0/authorize?client_id=" . getenv('NV_CLIENT_ID') . "&redirect_uri=" . urlencode($this->return_url($type)) . "&response_type=code&state=" . $state;
            $this->getResponseSend(['code' => 200, 'type' => 'NAVER_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        } else if ($type == getenv('DAUM_CODE')) {
            $url = "https://apis.daum.net/oauth2/authorize?client_id=" . getenv('DM_CLIENT_ID') . "&redirect_uri=" . urlencode($this->return_url($type)) . "&response_type=code";
            $this->getResponseSend(['code' => 200, 'type' => 'DAUM_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        } else if ($type == getenv('YAHOO_CODE')) {
            // https://auth.login.yahoo.co.jp/yconnect/v2/consent?session=w82oKcc8&display=popup&bcrumb=dD14QVFqYUImc2s9TGpGZ1VJMkhYOWdSNlpoWF9YNTZXcXNFbUE4LQ%3D%3D			
            $url = "https://auth.login.yahoo.co.jp/yconnect/v2/consent?session=" . getenv('YH_CLIENT_ID') . "&redirect_url=" . urlencode($this->return_url($type)) . "&scope=openid";
            $this->getResponseSend(['code' => 200, 'type' => 'YAHOO_CODE', 'server_mode' => $_SERVER['SERVER_NAME'], 'data' => $url], 200);
        }
    }

    /**
     *  @OA\Get(
     *      tags={"Oauth"},
     *      path="/oauth/sns_request_post",
     *      summary="SNS 로그인 요청 (FB, GG, AP)",
     *      @OA\Response(
     *          response=200,
     *          description="sns oauth start"
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *  @OA\Parameter(
     *   parameter="type",
     *   name="type",
     *   @OA\Schema(
     *     type="string",     
     *     default="GG"
     *   ),
     *   in="query",
     *   required=false
     *   ),
     * )
     */
    public function sns_request_post()
    {
        $rules = [
            'type' => 'required',
        ];

        $input = $this->getRequestInputGetType($this->request);
        $mt = microtime();
        $rand = mt_rand();
        $state = md5($mt . $rand);

        if (!$this->validateRequest($input, $rules)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    200
                );
        }

        $type = $input['type'];

        if ($type == getenv('TWITTER_CODE')) {
            $url = "https://twitter.com/oauth/request_token";
            $timestamp = time();
            $oauth = array(
                'oauth_callback' => $this->return_url($type),
                'oauth_consumer_key' => getenv('TW_CLIENT_ID'),
                'oauth_nonce' => $timestamp,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $timestamp,
                'oauth_version' => '1.0'
            );
            $baseString = $this->buildBaseString($url, $oauth);

            $compositeKey = $this->getCompositeKey(getenv('TW_CLIENT_SECRET'), null);
            $oauth_signature = base64_encode(hash_hmac('sha1', $baseString, $compositeKey, true));

            $oauth['oauth_signature'] = $oauth_signature;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);

            $header = $this->buildAuthorizationHeader($oauth);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
            $result = curl_exec($ch);

            $url = "https://api.twitter.com/oauth/authorize?" . $result;
            curl_close($ch);
            $this->response->redirect($url);
        } else if ($type == getenv('FACEBOOK_CODE')) {
            $state = bin2hex(openssl_random_pseudo_bytes(16));
            $url = "https://www.facebook.com/v13.0/dialog/oauth?client_id=" . getenv('FB_CLIENT_ID') . "&redirect_uri=" . urlencode('https://api./oauth/callback/FB') . "&scope=public_profile&state=$state";
            $this->response->redirect($url);
        } else if ($type == getenv('APPLE_CODE')) {
            $bundleId = "com.service.wevitt";
            $url = "https://appleid.apple.com/auth/authorize?response_type=code&client_id=" . $bundleId . "&scope=name+email&state=" . $state . "&response_mode=form_post&redirect_uri=" . urlencode('https://api./oauth/callback/AP');
            $this->response->redirect($url);
        } else if ($type == getenv('GOOGLE_CODE')) {
            log_message('info', $_SERVER['SERVER_NAME']);
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $url = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&redirect_uri=" . urlencode('http://localhost/oauth/callback/GG') . "&response_type=code&prompt=consent&client_id=" . getenv('GG_LOCAL_CLIENT_ID');
            } else if ($_SERVER['SERVER_NAME'] == 'dev1api.') {
                log_message('info', $_SERVER['SERVER_NAME']);
                $url = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&redirect_uri=" . urlencode('https://dev1api./oauth/callback/GG') . "&response_type=code&prompt=consent&client_id=" . getenv('GG_DEV_CLIENT_ID');
            } else {
                $url = "https://accounts.google.com/o/oauth2/auth?scope=email%20profile&redirect_uri=" . urlencode('https://api./oauth/callback/GG') . "&response_type=code&prompt=consent&client_id=" . getenv('GG_CLIENT_ID');
            }
            $this->response->redirect($url);
        } else if ($type == getenv('KAKAO_CODE')) {
            $url = "https://kauth.kakao.com/oauth/authorize?client_id=" . getenv('KK_CLIENT_ID') . "&redirect_uri=" . urlencode($this->return_url($type)) . "&response_type=code";
            $this->response->redirect($url);
        } else if ($type == getenv('NAVER_CODE')) {
            $url = "https://nid.naver.com/oauth2.0/authorize?client_id=" . getenv('NV_CLIENT_ID') . "&redirect_uri=" . urlencode($this->return_url($type)) . "&response_type=code&state=" . $state;
            $this->response->redirect($url);
        } else if ($type == getenv('DAUM_CODE')) {
            $url = "https://apis.daum.net/oauth2/authorize?client_id=" . getenv('DM_CLIENT_ID') . "&redirect_uri=" . urlencode($this->return_url($type)) . "&response_type=code";
            $this->response->redirect($url);
        } else if ($type == getenv('YAHOO_CODE')) {
            // https://auth.login.yahoo.co.jp/yconnect/v2/consent?session=w82oKcc8&display=popup&bcrumb=dD14QVFqYUImc2s9TGpGZ1VJMkhYOWdSNlpoWF9YNTZXcXNFbUE4LQ%3D%3D			
            $url = "https://auth.login.yahoo.co.jp/yconnect/v2/consent?session=" . getenv('YH_CLIENT_ID') . "&redirect_url=" . urlencode($this->return_url($type)) . "&scope=openid";
            $this->response->redirect($url);
        }
    }

    /**
     *  @OA\Put(
     *      tags={"Oauth"},
     *      path="/oauth/callback",
     *      summary="sns oauth return callback [각 플랫폼에 리턴 받는 주소 - api call back 주소 문서상 존재 직접호출 X ]",
     *      @OA\Response(
     *          response=200,
     *          description="sns oauth start"
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),
     * )
     */
    public function callback()
    {
        $input = $this->getRequestInputGetType($this->request);
        $input_post = $this->getRequestInputPostType($this->request);

        // if (!$this->validateRequest($input, $rules, $errors)) {
        //     return $this
        //         ->getResponse(
        //             $this->validator->getErrors(),
        //             200
        //         );
        // }

        $type = $input['type'] ?? "";
        $access_token = $input["access_token"] ?? "";
        $code = $input["code"] ?? "";
        $state = $input["state"] ?? "";
        $oauth_token = $input["oauth_token"] ?? "";
        $oauth_verifier = $input["oauth_verifier"] ?? "";
        $data = [];
        $message = "";

        $uri = service('uri');
        $mode =  $uri->getSegment(3);



        if ($type == getenv('TWITTER_CODE')) {
            // not email
        } else if ($type == getenv('FACEBOOK_CODE') || $mode == getenv('FACEBOOK_CODE')) {
            $url = "https://graph.facebook.com/v13.0/oauth/access_token?client_id=" . getenv('FB_CLIENT_ID') . "&redirect_uri=" . urlencode('https://api./oauth/callback/FB') . "&client_secret=" . getenv('FB_CLIENT_SECRET') . "&code=" . $code;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 0);
            $result = curl_exec($ch);
            $json = json_decode($result);
            //echo $json->{'error'}->{'code'};

            if (!empty($json->{'error'}->{'code'})) {
                echo $json->{'error'}->{'code'};
                exit;
            }
            //$message = '페이스북 통신 에러. 다시 시도해 주십시오.';

            $url = "https://graph.facebook.com/me?fields=email,name,picture&access_token=" . $json->{'access_token'};

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 0);
            $result = curl_exec($ch);
            $json = json_decode($result);
            $json_arr = json_decode($result, true);

            //log_message('notice', $result);

            log_message('info', 'callback FB >>>>');
            log_message('info', json_encode($json));

            $email = '';
            if (isset($json_arr['email'])) {
                $email = $json_arr['email'];
            }

            /**
             * stdClass Object ( 
             * [email] => lalionsg@gmail.com 
             * [name] => Sean Park 
             * [picture] => stdClass Object ( 
             *      [data] => stdClass Object ( 
             *          [height] => 50 [is_silhouette] => 1 
             *          [url] => https://scontent-sin6-4.xx.fbcdn.net/v/t1.30497-1/84628273_176159830277856_972693363922829312_n.jpg?stp=c15.0.50.50a_cp0_dst-jpg_p50x50&_nc_cat=1&ccb=1-7&_nc_sid=12b3be&_nc_ohc=znOHiwVT5CwAX9X00Aq&_nc_ht=scontent-sin6-4.xx&edm=AP4hL3IEAAAA&oh=00_AT9a3CxL_cSdyM3Mh1bYHt3C54r3xTTmBoqj7uOOvEjd9w&oe=62D83899 
             *      [width] => 50 ) ) 
             * [id] => 130674282973545 )
             */

            //print_r($json);
            curl_close($ch);

            $this->_sns_auth_proc([
                'hash_id' => microtime(true),
                'sns_id' => $json->{'id'},
                'email' => $email,
                'verified_email' => '',
                'name' => $json->{'name'},
                'locale' => '',
                'picture' => $json->{'picture'}->{'data'}->{'url'},
                'sns_type' => $this->return_sns_type($mode),
            ]);
        } else if ($type == getenv('APPLE_CODE') || $mode == getenv('APPLE_CODE')) {

            $code = $input_post['code'] ?? null;
            if ($code == null) {
                $arr = ["code" => 441, "message" => "not code response", "data" => []];
                return $this->getResponse($arr, 200);
            }

            $keyId = getenv('AP_KEY_ID'); //key file 등록된 ID
            $teamId = getenv('AP_TEAM_ID'); //service term id
            $bundleId = getenv('AP_BUNDLE_ID'); //bundle id

            $privKey = openssl_pkey_get_private(file_get_contents(FCPATH . '../AuthKey_U342Q4WRK9.p8'));
            $redirect_uri = getenv('AP_REDIRECT_RUI');

            $jwt_client_secret = JWT::encode([
                'iss' => $teamId, // 10-character team id, under your name
                'iat' => time(), // use strtotime('now') or Carbon::now()->timestamp
                'exp' => time() + 3600, // use strtotime('+60 days') or Carbon::now()->days(60)->timestamp
                'aud' => "https://appleid.apple.com", // it's constant
                'sub' => $bundleId, // 
            ], $privKey, 'ES256', $keyId);

            $data = [
                'client_id' => $bundleId,
                'client_secret' => $jwt_client_secret,
                'code' => $input_post['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://appleid.apple.com/auth/token');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $response = json_decode($response, true);

            /*
            {"access_token":"a22b2ac68592841a08dd0aabd8c51779d.0.rrqyu.rasUZ1qugLm5MPa3CcIiGQ",
                "token_type":"Bearer",
                "expires_in":3600,
                "refresh_token":"r93651f0879a24eceaa213ae897f199cc.0.rrqyu.bDKEKEqwlfr0PE_o7rd06A",
                "id_token":"eyJraWQiOiJXNldjT0tCIiwiYWxnIjoiUlMyNTYifQ.eyJpc3MiOiJodHRwczovL2FwcGxlaWQuYXBwbGUuY29tIiwiYXVkIjoiY29tLnNlcnZpY2Uud2V2aXR0IiwiZXhwIjoxNjU2MTQyOTU4LCJpYXQiOjE2NTYwNTY1NTgsInN1YiI6IjAwMTA4NC44MzFmMDFhNGMyYzA0MDFiYjZiNzkzN2E1YjQ3MjM1Yi4wNTQyIiwiYXRfaGFzaCI6IkxBUGN0bWNGanUxZkJYZmM3Q0dFRWciLCJlbWFpbCI6Inl0dHo2a2p2NmJAcHJpdmF0ZXJlbGF5LmFwcGxlaWQuY29tIiwiZW1haWxfdmVyaWZpZWQiOiJ0cnVlIiwiaXNfcHJpdmF0ZV9lbWFpbCI6InRydWUiLCJhdXRoX3RpbWUiOjE2NTYwNTY1NTYsIm5vbmNlX3N1cHBvcnRlZCI6dHJ1ZX0.p0ws8nfQq8QQCZBoksdss1Kl0p2Iwalsjgz6laK3KKcS6djEgdP6QFA32S8Mma-rpt2nSLOxxzajCqhAHG901XbFavfB68c-uJ3FKapgYIBO81XYrp-70HOni2TpKuIDrC35A6gLUTpqyIswOyy_2ualCcVMnYfDonj3N9ReGMqpX6k4LY9Mgct632YdvtD4EwShJvzv8l32h0Z-bVsXhn9F-rUx3mF_2uxhiIoMd705pEvbqoRdwbmrkXEo2MMDQ4A-vug7kxVfiPQWtdtIuCsDI43ShRuQrWqCWPgPn1_50jDAg7dE41yesFABp2t1olnzSPXASiayoBAZ7mP_og"
            }
            */
            /*
            stdClass Object
            (
                [iss] => https://appleid.apple.com
                [aud] => com.service.wevitt
                [exp] => 1656145132
                [iat] => 1656058732
                [sub] => 001084.831f01a4c2c0401bb6b7937a5b47235b.0542
                [at_hash] => AOOEFJh-xPqKiDxIrneeSg
                [email] => yttz6kjv6b@privaterelay.appleid.com
                [email_verified] => true
                [is_private_email] => true
                [auth_time] => 1656058731
                [nonce_supported] => 1
            )
            */

            try {
                //id_token decode jwt 
                $claims = explode('.', $response['id_token'])[1];
                $claims_data = json_decode(base64_decode($claims), true);
            } catch (\Exception $e) {
                //id_token not 
                $arr = ["code" => 441, "message" => json_encode($e), "data" => []];
                return $this->getResponse($arr, 200);
            }

            $name = $input_post['name'] ?? '';
            if (!$name) $name = 'Wevitt_User' . mt_rand(1, 999999); //없으면 임의로
            $this->_sns_auth_proc([
                'hash_id' => microtime(true),
                'sns_id' => $claims_data['sub'],
                'email' => $claims_data['email'],
                'verified_email' => $claims_data['email_verified'],
                'name' => $name,
                'locale' => '',
                'picture' => getenv('S3_SNS_DEFAULT_PICTURE'),
                'sns_type' => $this->return_sns_type($mode),
            ]);
        } else if ($type == getenv('GOOGLE_CODE') || $mode == getenv('GOOGLE_CODE')) {

            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $gg_client_secret = getenv('GG_LOCAL_CLIENT_SECRET');
                $gg_client = getenv('GG_LOCAL_CLIENT_ID');
                $redirect_uri = urlencode('http://localhost/oauth/callback/GG');
            } else if($_SERVER['SERVER_NAME'] == 'dev1api.') {
                $gg_client_secret = getenv('GG_DEV_CLIENT_SECRET');
                $gg_client = getenv('GG_DEV_CLIENT_ID');
                $redirect_uri = urlencode('https://dev1api./oauth/callback/GG');
            } else {
                $gg_client_secret = getenv('GG_CLIENT_SECRET');
                $gg_client = getenv('GG_CLIENT_ID');
                $redirect_uri = urlencode('https://api./oauth/callback/GG');
            }

            $url = "https://www.googleapis.com/oauth2/v3/token";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            $postdata = "code=" . $code . "&client_secret=" . $gg_client_secret . "&client_id=" . $gg_client . "&redirect_uri=" . $redirect_uri . "&grant_type=authorization_code&prompt=consent&include_granted_scopes=false&access_type=offline";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            $result = curl_exec($ch);
            $json = json_decode($result);

            if (!empty($json->{'error'})) {
                log_message('notice', $result);
                echo $json->{'error'};
                //echo $postdata;
                //print_r($json);
                //echo $message = "인증 절차가 잘못 되었습니다.  다시 시도해 주십시오.";
                exit;
            }

            $url = "https://www.googleapis.com/oauth2/v2/userinfo";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 0);
            $header = array("Authorization: " . $json->{'token_type'} . " " . $json->{'access_token'});
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $result = curl_exec($ch);
            $json = json_decode($result);

            $id = $json->{'id'};
            $email = $json->{'email'};
            $name = $json->{'name'};
            $locale = $json->{'locale'};
            $picture = $json->{'picture'};
            $verified_email = $json->{'verified_email'};
            curl_close($ch);

            $this->_sns_auth_proc([
                'hash_id' => microtime(true),
                'sns_id' => $json->{'id'},
                'email' => $json->{'email'},
                'verified_email' => $json->{'verified_email'},
                'name' => $json->{'name'},
                'locale' => $json->{'locale'},
                'picture' => $json->{'picture'},
                'sns_type' => $this->return_sns_type($mode),
            ]);

            //print_r($json);			
            /*
			 * [id] => 10669010121
			 * [email] => ceo@gmail.com
			 * [verified_email] => 1 
			 * [name] => Lee Sangkyo 
			 * [given_name] => Lee 
			 * [family_name] => Sangkyo 
			 * [link] => https://plus.google.com/10669010
			 * [picture] => https://lh3.googleusercontent.com/-XdUIqdMkCWA/DSD/photo.jpg
			 * [gender] => male 
			 * [locale] => ko
			 * */
        } else if ($type == getenv('NAVER_CODE')) {
            // if($state!=$orig_state) {
            // 	// TODO : redirect error
            // }
            $url = "https://nid.naver.com/oauth2.0/token?client_id=" . getenv('NV_CLIENT_ID') . "&client_secret=" . getenv('NV_CLIENT_SECRET') . "&grant_type=authorization_code&state=" . $state . "&code=" . $code;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            if ($errno = curl_errno($ch)) {
                $error_message = curl_strerror($errno);
            } else {
                $json = json_decode($result);
                $url = "https://openapi.naver.com/v1/nid/getUserProfile.xml";
                $header = array("Authorization: " . $json->{'token_type'} . " " . $json->{'access_token'});
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $result = curl_exec($ch);
                $xml = simplexml_load_string($result);

                if ($xml !== FALSE) {
                    $userInfo = array(
                        'email' => (string)$xml->response->email,
                        'nickname' => (string)$xml->response->nickname,
                        'age' => (string)$xml->response->age,
                        'birth' => (string)$xml->response->birthday,
                        'gender' => (string)$xml->response->gender,
                        'name' => (string)$xml->response->name,
                        'profImg' => (string)$xml->response->profile_image
                    );
                    $email = $userInfo['email'];
                    $name = $userInfo['name'];
                    $this->_sns_auth_proc($type, $email, $name);
                }
            }
            curl_close($ch);
        } else if ($type == getenv('DAUM_CODE')) {
            // 	$url="https://apis.daum.net/oauth2/token";			
            // 	$ch=curl_init();
            // 	curl_setopt($ch, CURLOPT_URL, $url);
            // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // 	curl_setopt($ch, CURLOPT_POST, 1);
            // 	$postdata="code=".$code."&client_id=".DM_CLIENT_ID."&client_secret=".DM_CLIENT_SECRET."&redirect_uri=".urlencode($this->return_url($type))."&grant_type=authorization_code";
            // 	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            // 	$result=curl_exec($ch);
            // 	$json=json_decode($result);

            // 	$url="https://apis.daum.net/user/v1/show.json?access_token=".$json->{'access_token'};
            // 	curl_setopt($ch, CURLOPT_URL, $url);
            // 	curl_setopt($ch, CURLOPT_POST, 0);
            // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // 	$result=curl_exec($ch);
            // 	$json=json_decode($result);
            // 	// email 없음
            // 	curl_close($ch);

        } else if ($type == getenv('YAHOO_CODE')) {
        } else if ($type == getenv('KAKAO_CODE')) {
            // 	$url="https://kauth.kakao.com/oauth/token";
            // 	$ch=curl_init();
            // 	curl_setopt($ch, CURLOPT_URL, $url);
            // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // 	curl_setopt($ch, CURLOPT_POST, 1);
            // 	$postdata="code=".$code."&client_id=".KO_CLIENT_ID."&redirect_uri=".urlencode($this->return_url($type))."&grant_type=authorization_code";
            // 	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            // 	$result=curl_exec($ch);
            // 	$json=json_decode($result);

            // 	$url="https://kapi.kakao.com/v1/user/me";
            // 	curl_setopt($ch, CURLOPT_URL, $url);
            // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // 	curl_setopt($ch, CURLOPT_POST, 0);
            // 	$header=array("Authorization: ".$json->{'token_type'}." ".$json->{'access_token'});
            // 	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            // 	$result=curl_exec($ch);
            // 	// echo $result;
            // 	// email 없음
            // 	curl_close($ch);
        }
    }

    /**
     * sns auth 프로세스 처리
     * login or register
     */
    private function _sns_auth_proc($data)
    {
        helper('jwt');
        $OauthModel = new OauthModel();
        $result_del_check = $OauthModel->findUserBySnsIdDelete($data["sns_id"]);
        if ($result_del_check) {
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                return $this->response->redirect('http://localhost/oauth/fail');
            } else if ($_SERVER['SERVER_NAME'] == 'dev') {
                return $this->response->redirect('/oauth/fail');
            } else { 
                return $this->response->redirect('/oauth/fail');
            }
        }

        $result = $OauthModel->findUserBySnsId($data["sns_id"]);
        // $sever_mode = getenv('CI_ENVIRONMENT');
        //log_message('error', json_encode($result));

        if ($result > 0) {

             //성공이면서 정상 회원 인지 체크
            if($result['status'] == 8) {
                //blacklist                
                if ($_SERVER['SERVER_NAME'] == 'localhost') {
                    return $this->response->redirect('http://localhost:3000/login?code=412&token=');
                } else if ($_SERVER['SERVER_NAME'] == 'dev1api.') {
                    return $this->response->redirect('https://dev1./login?code=412&token=');
                } else {
                    return $this->response->redirect('https:///login?code=412&token=');
                }
            }else if($result['status'] == 9) {
                //report ban
                if ($_SERVER['SERVER_NAME'] == 'localhost') {
                    return $this->response->redirect('http://localhost:3000/login?code=413&token=');
                } else if ($_SERVER['SERVER_NAME'] == 'dev1api.') {
                    return $this->response->redirect('https://dev1./login?code=413&token=');
                } else {
                    return $this->response->redirect('https:///login?code=413&token=');
                }
            }

            //login
            $jwt = getSignedJWTForUser($result['email'], $result['hash_id'], 10800000); //encode jwt  //3 hour
            $OauthModel->jwt_login_token($jwt, $result['member_id']);            
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $this->response->redirect('http://localhost:3000/login?code=201&token=' . $jwt);
            } else if ($_SERVER['SERVER_NAME'] == 'dev1api.') {
                return $this->response->redirect('https://dev1./login?code=201&token=' . $jwt);
            } else {                
                $this->response->redirect('https:///login?code=201&token=' . $jwt);
            }
        } else {
            //insert
            $OauthModel->save($data);
            $insert_id = $OauthModel->insertID;
            $OauthModel->badges_init($insert_id); //뱃지 초기화
            $OauthModel->alarm_init($insert_id); // 알람 초기화

            $result = $OauthModel->findUserBySnsId($data["sns_id"]);
            $jwt = getSignedJWTForUser($data['email'], $result['hash_id'], 10800000); //encode jwt  //3 hour
            $OauthModel->jwt_login_token($jwt, $insert_id);

            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $this->response->redirect('http://localhost:3000/login?code=201&token=' . $jwt);
            } else if ($_SERVER['SERVER_NAME'] == 'dev1api.') {
                return $this->response->redirect('https://dev1./login?code=201&token=' . $jwt);
            } else {
                $this->response->redirect('https:///login?code=201&token=' . $jwt);
            }
        }
    }

    /**
     *  @OA\Post(
     *      tags={"Oauth"},
     *      path="/oauth/push_token",
     *      security={{"bearerAuth": {}}},
     *      summary="push token  [푸시 토큰 등록]",
     *      @OA\Response(
     *          response=200,
     *          description=""
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(     
     *                  @OA\Property(
     *                      description="device_id 고유 디바이스 ID",
     *                      property="device_id",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      description="service type 1 web |2 android | 3 apple",
     *                      property="type",
     *                      type="number",
     *                  ), 
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function push_token()
    {
        $rules = [
            'device_id' => 'required',
            'type' => 'required',
        ];

        $errors = [];

        $input = $this->getRequestInputPostType($this->request);

        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    ["code" => 401, "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        $member_info = $this->init_jwt_info();
        $mem_id = $member_info['data']['member_id'];

        helper('jwt');
        $OauthModel = new OauthModel();
        $result = $OauthModel->push_token($mem_id, $input['device_id'], $input['type']);
        $this->getResponseSend(["code" => $result["code"], "message" => $result["message"], "data" => $result["data"]], 200);
    }


    /**
     *  @OA\Post(
     *      tags={"Oauth"},
     *      path="/oauth/logout",
     *      security={{"bearerAuth": {}}},
     *      summary="로그아웃 [푸시 토큰 삭제]",
     *      @OA\Response(
     *          response=200,
     *          description=""
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="service type 1 web |2 android | 3 apple",
     *                      property="type",
     *                      type="number",
     *                  ), 
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function logout()
    {
        $rules = [
            'type' => 'required',
        ];

        $errors = [];

        $input = $this->getRequestInputPostType($this->request);

        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    ["code" => 401, "message" => $this->validator->getErrors(), "data" => []],
                    200
                );
        }

        $member_info = $this->init_jwt_info();
        $mem_id = $member_info['data']['member_id'];

        $OauthModel = new OauthModel();
        $result = $OauthModel->push_token_del($mem_id, $input['type']);
        $this->getResponseSend(["code" => $result["code"], "message" => $result["message"], "data" => $result["data"]], 200);
    }

    /**
     *  @OA\Post(
     *      tags={"Oauth"},
     *      path="/oauth/sign_out",
     *      security={{"bearerAuth": {}}},
     *      summary="sign_out  [회원 탈퇴]",
     *      @OA\Response(
     *          response=200,
     *          description=""
     *      ),
     *      @OA\Response(
     *          response="default",
     *          description="an ""unexpected"" error"
     *      ),          
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/x-www-form-urlencoded",
     *              @OA\Schema(          
     *                  type="object",
     *              )
     *          )
     *      )
     *  )
     */
    public function sign_out()
    {
        $member_info = $this->init_jwt_info();
        $mem_id = $member_info['data']['member_id'];
        $OauthModel = new OauthModel();
        $OauthModel->sign_out($mem_id);
        $this->getResponseSend(["code" => 200, "message" => 'sign_out delete for member jwt data', "data" => []], 200);
    }

    // public function fb()
    // {
    //     echo $url = "https://graph.facebook.com/v13.0/oauth/access_token?client_id=" . getenv('FB_CLIENT_ID') . "&redirect_uri=" . urlencode('https://api./oauth/callback/FB') . "&client_secret=" . getenv('FB_CLIENT_SECRET') . "&code=";
    // }

    /**
     * 앱에서 로그인폼을 거쳐 로그인 끝내고난 데이터를 가져와서 로그인 시킴
     */
    public function facebook_forced_login()
    {
        $input = $this->getRequestInputPostType($this->request);

        if(! isset($input['payloads'])) {
            $this->getResponseSend(["code" => 440, "message" => 'not data payload', "data" => []], 200);
            exit;
        }

        $payloads = $input['payloads'];
        $json = json_decode($payloads, true);
        $id = $json['id'] ?? 0;
        
        if($id == 0) {
            $this->getResponseSend(["code" => 440, "message" => 'not data id', "data" => []], 200);
            exit;
        }

        log_message('info', 'facebook_forced_login>>>>');
        log_message('info', json_encode($json));

        $this->_sns_auth_proc_json([
            'hash_id' => microtime(true),
            'sns_id' => $json['id'] ?? 0,
            'email' => $json['email'] ?? '',
            'verified_email' => '',
            'name' => $json['name'] ?? '',
            'locale' => '',
            'picture' => $json['picture']['data']['url'] ?? '',
            'sns_type' => $this->return_sns_type('FB'),
        ]);
    }

    /**
     * sns auth 프로세스 처리 json 타입으로 줌
     * login or register
     */
    private function _sns_auth_proc_json($data)
    {
        helper('jwt');
        $OauthModel = new OauthModel();
        $result_del_check = $OauthModel->findUserBySnsIdDelete($data["sns_id"]);
        if ($result_del_check) {
            if ($_SERVER['SERVER_NAME'] == 'localhost') {
                $this->getResponseSend(["code" => 411, "message" => 'Users who have withdrawn from membership can sign up again after 7 days.', "token"=>"", "del"=>'true',  "data" => []], 200);
                exit;
            } else {
                //return $this->response->redirect('https:///login?code=411&del=true&token=');
                $this->getResponseSend(["code" => 411, "message" => 'Users who have withdrawn from membership can sign up again after 7 days.', "token"=>"", "del"=>'true',  "data" => []], 200);
                exit;
            }
        }

        $result = $OauthModel->findUserBySnsId($data["sns_id"]);
        // $sever_mode = getenv('CI_ENVIRONMENT');

        if ($result > 0) {

            //login
            $jwt = getSignedJWTForUser($result['email'], $result['hash_id'], 10800000); //encode jwt  //3 hour
            $OauthModel->jwt_login_token($jwt, $result['member_id']);
            //$this->session->set(["token" => $jwt]);
            if ($_SERVER['SERVER_NAME'] == 'localhost') {                
                $this->getResponseSend(["code" => 201, "message" => 'login', "token"=>$jwt, "data" => []], 200);
                exit;
            } else {                
                $this->getResponseSend(["code" => 201, "message" => 'login', "token"=>$jwt, "data" => []], 200);
                exit;
            }
        } else {
            //insert
            $OauthModel->save($data);
            $insert_id = $OauthModel->insertID;
            $OauthModel->badges_init($insert_id); //뱃지 초기화
            $OauthModel->alarm_init($insert_id); // 알람 초기화
            
            $result = $OauthModel->findUserBySnsId($data["sns_id"]);
            $jwt = getSignedJWTForUser($data['email'], $result['hash_id'], 10800000); //encode jwt  //3 hour
            // log_message('notice', json_encode($jwt));

            $OauthModel->jwt_login_token($jwt, $insert_id);

            if ($_SERVER['SERVER_NAME'] == 'localhost') {                
                $this->getResponseSend(["code" => 201, "message" => 'insert', "token"=>$jwt, "data" => []], 200);
                exit;
            } else {                
                $this->getResponseSend(["code" => 201, "message" => 'insert', "token"=>$jwt, "data" => []], 200);
                exit;
            }
        }
    }

    public function server_name()
    {
        echo$_SERVER['SERVER_NAME'];
    }
}
