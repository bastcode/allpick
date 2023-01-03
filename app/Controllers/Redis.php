<?php

namespace App\Controllers;

use App\Models\OauthModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use Predis;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;


class Redis extends BaseController
{

    public function __construct()
    {
    }

    /**
     * 챗팅 로그 리스트 
     * chat 에서 호출 함
     */
    public function chat_log_list(array $data)
    {

        //배열형태로 만들어주고
        $redis_data = [];
        $count = 0;
        $redis_paging = 60;

        $client = new Predis\Client(['host' => 'redis627']);

        if( isset($data['id']) && $data['id'] > 0 ) {
            // if( ($redis_paging - $data['id']) > 0 ) {
                $lists =  $client->lrange($data['room_id'],  0, -1); // 전체데이터 가져옴
                foreach ($lists as $key => $val) {            
                    $json_data = json_decode($val, true);
                    $id = $json_data['id'] ?? 0;
                    if($id <= $data['id']){                        
                        if($count >= $redis_paging) break; //카운터 갯수 넘어가면 중지
                        $redis_data[$key] = json_decode($val, true);
                        $count++;                        
                    }
                }
            // }else{
            //     $lists = []; // 더 돌게 없음
            // }
        }else{
            $lists =  $client->lrange($data['room_id'], 0, $redis_paging -1); //60개 가져옴
            foreach ($lists as $key => $val) {
                $redis_data[$key] = json_decode($val, true);
            }
        }

        return $redis_data;

        //print_r(json_encode($lists));
    }


    /**
     * room_id, onner_hash_id, send_hash_id, message, type
     * 현재 사용안함 > nodejs 에서 즉시 쌓기 중
     */
    public function chat_add(array $data)
    {
        //$data = $this->getRequestInputGetType($this->request);
        $client = new Predis\Client(['host' => 'redis627']);
        //roor_id,  send_hash_id , message , send_time, check_onner == 오너가읽었나 true|false, check_deler = 구매자가읽었나 true|false

        $data['room_id'] = "";
        $data['onner_hash_id'] = "";
        $data['send_hash_id'] = "";
        $data['message'] = "chat message !";
        $data['send_time'] = date("Y-m-d H:i:s", time());
        if ($data['onner_hash_id'] == $data['send_hash_id']) {
            $data['check_onner'] = true;
            $data['check_deler'] = false;
        } else {
            $data['check_onner'] = false;
            $data['check_deler'] = true;
        }

        log_message('notice', ' redis_add ==' . json_encode($data));

        $result = $client->lpush($data["room_id"], json_encode($data));

        if ($result) {
            $client->set("chat_push:" . $data['room_id'], 'expire room id');
            $client->expire("chat_push:" . $data['room_id'], 10);
        }
    }



    /**
     * 메세지 푸시 
     * 룸번호 기준으로 조회하고 라스트메세지 업데이트 및 FCM push
     * nodejs 에서 키종료 이벤트로 호출
     * last message는 역순리스트 0번째 메세지를 공통으로 가져간다 새로 체크할 메세지가 아니면 보내지 않기 때문에 공통으로 사용
     */
    public function chat_push()
    {

        $uri = service('uri');
        $room_id =  $uri->getSegment(3);
        $client = new Predis\Client(['host' => 'redis627']);
        $lists =  $client->lrange($room_id, 0, 100);

        //echo '<pre>'; print_r($lists);

        //배열형태로 만들어주고
        $redis_data = [];
        foreach ($lists as $key => $val) {
            $redis_data[$key] = json_decode($val, true);
        }

        //echo '<pre>'; print_r($redis_data);

        //최신 읽음 메세지 찾기
        $onner_count = 0;
        $deler_count = 0;
        // $onner_data = [];
        // $deler_data = [];

        $new_message_onner = "";
        $new_message_dealer = "";
        $last_message = "";
        $last_message_type = "";

        //오너가 쓴 메세지가 몇개나 쌓였나
        //0개이면 보낼메세지 없음 최신이라서 !
        $i = 0;
        foreach ($redis_data as $key => $val) {

            if($val['type'] == 'system') continue; // system message pass

            //메세지 없을때 담김 첫번째 메세지
            if(!$last_message) {
                $last_message = $val['message'];
                $last_message_type = $val['type'];
            }

            $onner_id = $val['onner_hash_id'];
            if ($val['check_onner'] === true)  break;
            if ($val['send_hash_id'] != $onner_id) {
                $onner_count =  ++$i;
                if ($onner_count === 1) {
                    $new_message_onner = $val['message'];
                    //log_message('error', 'foreach onner message  == '. $new_message_onner);
                }
            }
        }

        //딜러가 쓴 메세지가 몇개나 쌓였나
        $i = 0;
        foreach ($redis_data as $key => $val) {
            if($val['type'] == 'system') continue; // system message pass

            $onner_id = $val['onner_hash_id'];
            if ($val['check_deler'] === true)  break;
            if ($val['send_hash_id'] == $onner_id) {
                $deler_count =  ++$i;
                if ($deler_count === 1) {
                    $new_message_dealer = $val['message'];                    
                }
            }
        }

        $OauthModel = new OauthModel();
        $push_token = $OauthModel->get_push_token($room_id);
        $member_alarm = [];
        try {
            $member_alarm = $OauthModel->member_alarm_in($push_token);
        } catch (Exception $ex) {
            log_message('error', 'chat push member alarm err');
        }
        
        //챗팅 끄면 메세지 안날아감 디바이스 토큰 제거 -- 개인설정이 우선
        $ban_member_id = null;        
        foreach ($member_alarm as $key => $val) {
            
            //판매자 메세지 0개이면 안보냄
            if ($val['member_id'] == $push_token['onner_id'] && $onner_count == 0) {
                $push_token['onner_device_token'] = null;                
            }

            if($val['member_id'] == $push_token['onner_id'] && $onner_count >= 1)
            {
                $ban_member_id = $val['member_id'];                
                // log_message('error', 'onner id == '. $ban_member_id);
            }

            //구매자 메세지 0개이면 안보냄
            if ($val['member_id'] == $push_token['dealer_id'] && $deler_count == 0) {
                $push_token['dealer_device_token'] = null;                
            }

            if($val['member_id'] == $push_token['dealer_id'] && $deler_count >= 1)
            {
                $ban_member_id = $val['member_id'];
                // log_message('error', 'dealer id == '. $ban_member_id);
            }
        }

        //ban to ban user
        $ban = $OauthModel->user_ban_list_to_push($ban_member_id,$push_token['onner_id'], $push_token['dealer_id']);
        foreach ($ban['data'] as $key => $val) {
            //차단 상대값이 있으면 받는 사람의 토큰을 비움
            if ($val['ban_id'] == $push_token['dealer_id']) $push_token['onner_device_token'] = null; 
            if ($val['ban_id'] == $push_token['onner_id']) $push_token['dealer_device_token'] = null;
        }

        
        //챗팅방 마다 온오프 필터
        if($push_token['bell_status_onner'] == 0) $push_token['onner_device_token'] = null;        
        if($push_token['chat_status'] == 4 ) $push_token['onner_device_token'] = null;
        if($push_token['bell_status_dealer'] == 0) $push_token['dealer_device_token'] = null;
        if($push_token['chat_status_dealer'] == 4) $push_token['dealer_device_token'] = null;

        //라스트메세지 업데이트
        $OauthModel->update_last_message($room_id, $last_message, $last_message_type);

        //딜러가 쓴 글이 쌓여있으면 오너에게 보낸다
        $link_url = "https://wevitt.com/chatlist";
        if($_SERVER['SERVER_NAME'] == 'dev1.wevitt.com' ||  $_SERVER['SERVER_NAME'] == 'dev1api.wevitt.com') {
            $link_url = "https://dev1.wevitt.com/chatlist";
        }


        if ($onner_count > 0) {
            $push_tokens_onner = $OauthModel->all_push_token($push_token['onner_id']);
        }

        if ($onner_count > 0 && $push_token['onner_device_token']) {
            //fcm
            foreach($push_tokens_onner as $key =>$val) {
                // $token = $push_token['onner_device_token'];
                $token = $val["device_id"];
                $token_type = $val["type"];

                $title = "a new message";
                $body = $new_message_onner;
                log_message('notice', 'fcm caht onner');

                if ($token_type == 1) {

                    $data = [
                        'type' => 'chat_push',
                        'link_url' => $link_url
                    ];            
                    $this->fcm_web_message($token, $title, $body, $data);
                    log_message('notice', 'fcm caht push web ' . $token);
                } else if ($token_type == 2) {
                    //android back push                    
                    $data = [
                        'title' => $title,
                        'body' => $body,
                        'type' => 'chat_push',
                        'link_url' => $link_url
                    ];
                    $this->fcm_app_message_noti($token, $data);
                    log_message('notice', 'fcm caht push android ' . $token);
                } else {
                    //ios push
                    $data = [
                        'type' => 'chat_push',
                        'link_url' => $link_url
                    ];
                    $this->fcm_app_message($token, $title, $body, $data);
                    log_message('notice', 'fcm caht push ios ' . $token);
                }
            }
        }


        if ($deler_count > 0) {
            $push_tokens_delaer = $OauthModel->all_push_token($push_token['onner_id']);
        }

        //오너가쓴 글이 쌓여있으면 딜러에게 보낸다
        if ($deler_count > 0 && $push_token['dealer_device_token']) {
            foreach($push_tokens_delaer as $key=>$val) {
                 //fcm            
                // $token = $push_token['dealer_device_token'];
                $token = $val["device_id"];
                $token_type = $val["type"];

                $title = "a new message";
                $body = $new_message_dealer;
                log_message('notice', 'fcm caht dealer');
                if ($token_type == 1) {
                    //web                
                    $data = [
                        'type' => 'chat_push',
                        'link_url' => $link_url
                    ];            
                    $this->fcm_web_message($token, $title, $body, $data);
                    log_message('notice', 'fcm caht push web ' . $token);
                } else if ($token_type == 2) {
                    //android back push                    
                    $data = [
                        'title' => $title,
                        'body' => $body,
                        'type' => 'chat_push',
                        'link_url' => $link_url
                    ];
                    $this->fcm_app_message_noti($token, $data);
                    log_message('notice', 'fcm caht push android ' . $token);
                } else if ($token_type == 3) {
                    //web ios push
                    $data = [
                        'type' => 'chat_push',
                        'link_url' => $link_url
                    ];
                    $this->fcm_app_message($token, $title, $body, $data);
                    log_message('notice', 'fcm caht push ios ' . $token);
                }
            }
        }
    }

    /**
     * ios
     */
    private function fcm_app_message(string $token, string $title, string $body, array $data)
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(FCPATH . '../wevit-5cb4d-firebase-adminsdk-dpdq2-a17def894a.json')
                ->withDatabaseUri('https://Wevitt.firebaseio.com');
            $messaging = $factory->createMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification([
                    'title' => $title,
                    'body' => $body
                ])->withData($data);
            return $messaging->send($message);
        } catch (Exception $ex) {
            log_message('error', 'chat fcm ios error ex = ' . json_encode($ex));
        }
    }

    /**
     * back ground android
     */
    private function fcm_app_message_noti(string $token, array $data)
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(FCPATH . '../wevit-5cb4d-firebase-adminsdk-dpdq2-a17def894a.json')
                ->withDatabaseUri('https://Wevitt.firebaseio.com');
            $messaging = $factory->createMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withData($data);
            return $messaging->send($message);
        } catch (Exception $ex) {
            log_message('error', 'chat fcm ios error ex = ' . json_encode($ex));
        }
    }

    /**
     * web
     */
    private function fcm_web_message(string $token, string $title, string $body, array $data)
    {
        try {
            $deviceTokens = [$token];
            $factory = (new Factory)
            ->withServiceAccount(FCPATH . '../wevit-5cb4d-firebase-adminsdk-dpdq2-a17def894a.json')
            ->withDatabaseUri('https://Wevitt.firebaseio.com');
            $messaging = $factory->createMessaging();

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification([
                    'title' => $title,
                    'body' => $body
                ])->withData($data);
            log_message('info', 'fcm web message'. json_encode($message));

            
            $report =     $messaging->sendMulticast($message, $deviceTokens);
            echo 'Successful sends: '.$report->successes()->count().PHP_EOL;
            echo 'Failed sends: '.$report->failures()->count().PHP_EOL;

            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    echo $failure->error()->getMessage().PHP_EOL;
                }
            }

            // The following methods return arrays with registration token strings
            $successfulTargets = $report->validTokens(); // string[]

            print_r($successfulTargets);

            // Unknown tokens are tokens that are valid but not know to the currently
            // used Firebase project. This can, for example, happen when you are
            // sending from a project on a staging environment to tokens in a
            // production environment
            $unknownTargets = $report->unknownTokens(); // string[]

            // Invalid (=malformed) tokens
            $invalidTargets = $report->invalidTokens(); // string[]

        } catch(Exception $ex) {
            log_message('error', 'fcm web message !'. json_encode($ex));
        }
        
    }

    /**
     * 룸아이디로만 조회
     * 리턴 값에 유저 해쉬값으로 판매자인지 구매자인지 구분한다
     * 같은 채팅 메세지를 2번 루프 돌면서 오너랑 딜러 메세지를 구분함
     */
    public function inner_get_chat_list($room_id)
    {
        $client = new Predis\Client(['host' => 'redis627']);
        $lists =  $client->lrange($room_id, 0, 100);

        //배열형태로 만들어주고
        $redis_data = [];
        foreach ($lists as $key => $val) {
            $redis_data[$key] = json_decode($val, true);
        }

        //최신 읽음 메세지 찾기
        $onner_count = 0;
        $deler_count = 0;
        $new_message = "";
        $onner_id = "";
        
        //onner
        foreach ($redis_data as $key => $val) {
            if($val['type'] == 'system') continue; //system message pass
            if(!$new_message) $new_message = $val['message'];
            if(!$onner_id) {
                if($val['onner_hash_id']) $onner_id = $val['onner_hash_id'];
            }
            
            if ($val['check_onner'] === true || $val['check_onner'] === 1)  break;
            if ($val['send_hash_id'] != $onner_id) {
                $onner_count =  $onner_count + 1;
            }
        }

        //dealer
        foreach ($redis_data as $key => $val) {
            if($val['type'] == 'system') continue; //system message pass            
            if(!$onner_id) {
                if($val['onner_hash_id']) $onner_id = $val['onner_hash_id'];
            }
            if ($val['check_deler'] === true) break;
            if ($val['send_hash_id'] == $onner_id) {
                $deler_count =  $deler_count + 1;                
            }
        }

        return [
            'onner_hash_id' => $onner_id,
            'last_message' => $new_message,
            'onner_count' => $onner_count,
            'deler_count' => $deler_count,
        ];
    }

    public function zadd(string $key)
    {
        $client = new Predis\Client(['host' => 'redis627']);
        $score = $client->zscore("keyword", $key);
        $data = $client->zadd("keyword", [$key => $score + 1]);
    }

    public function zrevrange()
    {

        $client = new Predis\Client(['host' => 'redis627']);
        return $client->zrevrange("keyword",  0, 10, "withscores");
    }

    /**
     * 읽음처리 업데이트
     */
    public function chat_update($room_id, $send_hash_id)
    {
        $client = new Predis\Client(['host' => 'redis627']);
        $lists =  $client->lrange($room_id, 0, 0);

        //log_message('notice', $room_id."::".$send_hash_id);

        //log_message('notice', json_encode($lists));

        $chat_data = [];
        //배열형태로 만들어주고        
        foreach ($lists as $key => $val) {
            $chat_data = json_decode($val, true); //첫번째 데이터
        }

        //데이터가 있을대만 업데이트
        if (count($chat_data) > 0) {
            if ($chat_data['onner_hash_id'] == $send_hash_id) {
                $chat_data['check_onner'] = true;
            } else {
                $chat_data['check_deler'] = true;
            }
            //log_message('notice', "chat_update::" . $room_id . "::" . $send_hash_id);
            $client->lset($room_id, 0, json_encode($chat_data));
        }
    }

}
