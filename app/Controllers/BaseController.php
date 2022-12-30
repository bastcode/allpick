<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Services;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['jwt'];


    protected $session = null;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
        // $this->session = \Config\Services::session();
    }

    public function getResponse(
        array $responseBody,
        int $code = ResponseInterface::HTTP_OK
    ) {
        return $this
            ->response
            ->setStatusCode($code)
            ->setJSON($responseBody);
    }

    public function getResponseSend(
        array $responseBody,
        int $code = ResponseInterface::HTTP_OK
    ) {
        return $this
            ->response
            ->setStatusCode($code)
            ->setJSON($responseBody)
            ->sendBody();
    }

    //json input check
    public function getRequestInput(IncomingRequest $request)
    {
        $input = $request->getPost();
        if (empty($input)) {
            //convert request body to associative array
            if ($request->getBody()) $input = json_decode($request->getBody(), true);
        }
        return $input;
    }
    //input post check
    public function getRequestInputPostType(IncomingRequest $request)
    {
        $input = $request->getVar();
        if (!empty($input)) {
            $input = json_decode(json_encode($input), true); //convert array
        }
        return $input;
    }

    public function getRequestInputGetType(IncomingRequest $request)
    {
        $input = $request->getGet();
        if (empty($input)) {
            //convert request body to associative array
            if ($request->getBody()) $input = json_decode($request->getBody(), true);
        }
        return $input;
    }

    public function validateRequest($input, array $rules, array $messages = [])
    {
        $this->validator = Services::Validation()->setRules($rules);
        // If you replace the $rules array with the name of the group
        if (is_string($rules)) {
            $validation = config('Validation');

            // If the rule wasn't found in the \Config\Validation, we
            // should throw an exception so the developer can find it.
            if (!isset($validation->$rules)) {
                throw ValidationException::forRuleNotFound($rules);
            }

            // If no error message is defined, use the error message in the Config\Validation file
            if (!$messages) {
                $errorName = $rules . '_errors';
                $messages = $validation->$errorName ?? [];
            }

            $rules = $validation->$rules;
        }
        return $this->validator->setRules($rules, $messages)->run($input);
    }

    public function init_jwt_check()
    {
        $input = $this->getRequestInputGetType($this->request); //check get
        $input["token"] = $input["token"] ?? false;
        if ($input["token"] === false) {
            $input = $this->getRequestInputPostType($this->request); //check post
        }

        $request = \Config\Services::request();
        $header = $request->getHeader("Authorization");
        $bearer_token = false;
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $bearer_token = $matches[1];
            }
        }

        $input["token"] = $input["token"] ?? false;

        // $session = $this->session->get();
        // $token = $session["token"] ?? false; //session token
        $token = $bearer_token;
        // if($token === false) $token = $bearer_token; //세션이 없으면 Bearer token        
        if ($token === false) $token = $input["token"]; //없으면  get post token 입력값 
        if ($token === false) {
            //모든값이 없으면 토큰이 없음            
            $this->getResponseSend(["code" => 401, "message" => "token missing plz login https://wevitt.com/login"], 200);
            exit;
        }

        $return = validateJWTJustCheck($token);
        if ($return["code"] == 500) {
            //토큰 체크 실패 부정 접속 혹은 time out 3 hour            
            $this->getResponseSend(["code" => 401, "message" => "token validate fail  login https://wevitt.com/login"], 200);
            exit;
        }
    }

    /**
     * 토큰으로 조회
     * 회원 정보 리턴
     * ////////////////////     
     * 로그인 및 토큰 에러 code 정리
     * 401 토큰 전송값 없음
     * 411 탈퇴한 계정
     * 412 블랙리스트 계정 [주로 결제관련 관리자에 의한 추방]
     * 413 리포트당한 계정
     * 441 회원데이터 조회 안됨
     * 442 JWT 토큰 인증 유효시간 지남 [현재 체크안함]     
     * 505 토큰 값 잘못됨 [위 변조]
     */
    public function init_jwt_info()
    {
        $input = $this->getRequestInputGetType($this->request); //check get
        $input["token"] = $input["token"] ?? false;
        if ($input["token"] === false) {
            $input = $this->getRequestInputPostType($this->request); //check post
        }
        $input["token"] = $input["token"] ?? false; //둘다 빈값 인지 오퍼레이션처리

        $request = \Config\Services::request();
        $header = $request->getHeader("Authorization");
        $bearer_token = false;
        if (!empty($header)) {
            //log_message('info','Bearer Header');
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $bearer_token = $matches[1];
                //log_message('info', json_encode($bearer_token ));
            }
        }

        $token = $bearer_token; //기본 토큰에  Bearer 부터 체크
        if ($token === false) $token = $input["token"]; //없으면  get post token 입력값 
        if ($token === false) {
            $this->getResponseSend(["code" => 401, "message" => "token missing jwt plz login", 'data' => []], 200);
            exit;
        }

        //log_message('error', json_encode($token));
        $return = validateJWTMemberToken($token);
        //log_message('info', json_encode($return));
        if ($return["code"] == 200) {
            //성공이면서 정상 회원 인지 체크
            if ($return['data']['status'] == 4) {
                $this->getResponseSend(['code' => 411, 'message' => 'withdrawn account', 'data' => []], 200);
                exit;
            } else if ($return['data']['status'] == 8) {
                $this->getResponseSend(['code' => 412, 'message' => 'black list account', 'data' => []], 200);
                exit;
            } else if ($return['data']['status'] == 9) {
                $this->getResponseSend(['code' => 413, 'message' => 'report ban account', 'data' => []], 200);
                exit;
            }
            return $return;
        } else {
            //441 회원데이터 조회안됨 , 505 토큰값 오류
            $this->getResponseSend($return, 200);
            exit;
        }
    }


    /**
     * 토큰으로 조회
     * 회원 정보 리턴 - not check
     * code massage data
     */
    public function init_jwt_info_pass()
    {
        $input = $this->getRequestInputGetType($this->request); //check get
        $input["token"] = $input["token"] ?? false;
        if ($input["token"] === false) {
            $input = $this->getRequestInputPostType($this->request); //check post
        }
        $input["token"] = $input["token"] ?? false; //둘다 빈값 인지 오퍼레이션처리

        $request = \Config\Services::request();
        $header = $request->getHeader("Authorization");
        $bearer_token = false;
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $bearer_token = $matches[1];
            }
        }

        $token = $bearer_token; //기본 토큰에  Bearer 부터 체크
        if ($token === false) $token = $input["token"]; //없으면  get post token 입력값
        if ($token === false) {
            return ["code" => 305, "message" => "passing token"];
        } else {
            return ["code" => 301, "message" => "is token check"];
        }
    }
}
