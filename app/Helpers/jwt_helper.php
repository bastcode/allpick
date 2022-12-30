<?php

use App\Models\UserModel;
use App\Models\OauthModel;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;

function getJWTFromRequest($authenticationHeader): string
{
    if (is_null($authenticationHeader)) { //JWT is absent
        throw new Exception('Missing or invalid JWT in request');
    }
    //JWT is sent from client in the format Bearer XXXXXXXXX
    return explode(' ', $authenticationHeader)[1];
}

/**
 * sample code
 */
function validateJWTFromRequest(string $encodedToken)
{
    $key = Services::getSecretKey();
    //$decodedToken = JWT::decode($encodedToken, $key, 'HS512');
    $decodedToken = JWT::decode($encodedToken, new Key($key, 'HS512'));
    
    var_dump($decodedToken); exit;
    $userModel = new UserModel();
    $userModel->findUserByEmailAddress($decodedToken->email);
}

/**
 * 회원 조회용 토큰 체크
 */
function validateJWTMemberToken(string $encodedToken)
{
    $key = Services::getSecretKey();

    try{
        $decodedToken = JWT::decode($encodedToken, new Key($key, 'HS512'));

        // 우선 토큰 시간 체크 안함
        // if($decodedToken->exp < time()) {
        //     return ["code"=> 442, "message"=>"JWT Exp time over set to 3 hour", "data"=> []];    
        // }

        $oauthModel = new OauthModel();
        $result = $oauthModel->findUserHash($decodedToken->hash_id);
        // log_message('notice', $decodedToken->hash_id);
        // log_message('notice', json_encode($result));
        if($result)
            return ["code"=> 200, "message"=>"ok", "data"=> $result];
        else
            return ["code"=> 441, "message"=>"not member data", "data"=> [json_encode($decodedToken)]];
    }catch (Exception $ex){
        log_message('notice', $decodedToken->hash_id.json_encode($result).json_encode($ex));
        //jwt 시그니쳐 오류
        return ["code"=> 505, "message"=>"Invalid Signature JWT", "data"=> []];
    }
}

/**
 * JWT 유효성 체크
 * just check
 */
function validateJWTJustCheck(string $encodedToken)
{
    $key = Services::getSecretKey();
    try{
        JWT::decode($encodedToken, new Key($key, 'HS512'));
        return ["code"=> 200, "message"=>"ok"];
    }catch (Exception $ex){
        return ["code"=> 505, "message"=>"Invalid Just Signature Error", "data"=> []];
    }
}

/**
 * JWT 토큰 생성 - WEB
 */
function getSignedJWTForUser(string $email, string $hash_id, int $time = 0)
{
    $issuedAtTime = time();
    $tokenTimeToLive = getenv('JWT_TIME_TO_LIVE');
    if($time > 0) $tokenTimeToLive = $time; //milliseconds
    $tokenExpiration = $issuedAtTime + $tokenTimeToLive;
    $payload = [
        'email' => $email,
        'hash_id' => $hash_id,
        'iat' => $issuedAtTime,
        'exp' => $tokenExpiration,
    ];

    $jwt = JWT::encode($payload, Services::getSecretKey(), "HS512"); //version default alg add
    return $jwt;
}