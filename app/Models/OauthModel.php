<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class OauthModel extends Model
{
    protected $table = 'member';
    protected $allowedFields = [
        'hash_id',
        'sns_id',
        'name',
        'email',
        'verified_email',
        'locale',
        'picture',
        'sns_type'
    ];
    protected $updatedField = 'updated_at';
    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array
    {
        return $data;
        // return $this->getUpdatedDataWithHashedPassword($data);
    }

    public function findUserByEmailAddress(string $emailAddress)
    {
        $user = $this
            ->asArray()
            ->where(['email' => $emailAddress])
            ->first();

        if (!$user)
            throw new Exception('User does not exist for specified email address');

        return $user;
    }

    public function findUserBySnsId(string $sns_id)
    {
        return $this
            ->asArray()
            ->where(['sns_id' => $sns_id])
            ->where('status !=', 4)
            ->first();
    }

    public function findUserBySnsIdDelete(string $sns_id)
    {
        $get_day = getenv('MEMBER_DELETE_DAY');

        $build = $this->db->table('member')
            ->selectCount('*', 'cnt')
            ->where('sns_id', $sns_id)
            ->where('status', 4)
            ->orderBy('member_id', 'desc');

        $member = $build->get()->getRowArray();

        if ($member['cnt'] > 0) {
            //탈퇴 흔적이 있으면 재검사
            $build = $this->db->table('member')
                ->selectCount('*', 'cnt')
                ->where('sns_id', $sns_id)
                ->where('status', 4)
                ->where('updated_at >= DATE_ADD(NOW() , INTERVAL ' . $get_day . ' DAY)')
                ->orderBy('member_id', 'desc');
            $delete = $build->get()->getRowArray();

            if ($delete['cnt'] > 0) {
                //잡았다 요놈
                log_message('error', 'del member 7day ' . $sns_id);
                return true;
            } else {
                //탈퇴 기간 7일 지남
                return false;
            }
        } else {
            //탈퇴 한적 없음
            return false;
        }
    }

    public function badges_init(int $mem_id)
    {
        $this->db->table('member_badges')->insert(['member_id' => $mem_id, 'badges_id' => 1]);
    }

    public function alarm_init(int $mem_id)
    {
        $this->db->table('member_alarm')->insert(['member_id' => $mem_id]);
    }


    public function jwt_login_token(string $jwt, int  $mem_id)
    {
        $this->db->table('jwt')->delete(['member_id' => $mem_id]);
        $this->db->table('jwt')->insert(['member_id' => $mem_id, 'token' => $jwt]);
    }

    public function findUserEmail(string $email)
    {
        $user = $this
            ->asArray()
            ->where(['email' => $email])
            ->first();
        return $user;
    }

    public function findUserHash(string $hash_id)
    {
        return $this->asArray()
            ->where(['hash_id' => $hash_id])
            ->first();
    }


    public function  push_token(int $mem_id,  string $device_id, int $type)
    {

        $this->db->table('member_push_toekn')->where('member_id', $mem_id)->where('type', $type)->delete();
        $result = $this->db->table('member_push_toekn')->insert(['member_id' => $mem_id, 'device_id' => $device_id, 'type' => $type]);

        if ($result) {
            return ['code' => 200, 'message' => 'ok', 'data' => []];
        } else {
            return ['code' => 501, 'message' => 'database error push_token', 'data' => []];
        }
    }

    public function  push_token_del(int $mem_id, int $type)
    {
        $result = $this->db->table('member_push_toekn')->where('member_id', $mem_id)->where('type', $type)->delete();

        if ($result) {
            return ['code' => 200, 'message' => 'ok', 'data' => []];
        } else {
            return ['code' => 501, 'message' => 'database error push_token_del', 'data' => []];
        }
    }

    public function sign_out(int $mem_id)
    {
        $this->db->table('member_push_toekn')->delete(['member_id' => $mem_id]); //푸시토큰삭제
        $this->db->table('jwt')->delete(['member_id' => $mem_id]); //세션토큰삭제
        $this->db->table('member_geo')->delete(['member_id' => $mem_id]); //지역정보 삭제
        $this->db->table('member_keywords')->delete(['member_id' => $mem_id]); //키워드삭제
        $this->db->table('wishlist')->delete(['member_id' => $mem_id]); //좋아요리스트 삭제
        $this->db->table('product_offer')->update(['chat_status_dealer' => 3], ['dealer_id' => $mem_id], 1); //딜러인것 탈퇴로 변경


        //회원정보 비접속으로 변경
        $this->db->table('member')
            ->set('status', 4)
            ->set('email', '')
            ->set('name', '')
            ->set('picture', 'https://wevitt.s3.ap-southeast-1.amazonaws.com/wevitt_assets/wevitt_sim.png')
            ->set('updated_at', 'now()', false)
            ->where('member_id', $mem_id)
            ->update();

        $product = $this->db->table('product')->select('mem_idx, product_id')
            ->where('mem_idx', $mem_id)
            ->where('status <=', 2) //예약 이하만 가능
            ->get()->getResultArray();
        $product_id_array = []; //미등록, 등록, 예약  상품 ID
        $data = [];
        foreach ($product as $key => $val) {
            $product_id_array[$key] = $val['mem_idx'];
            $data[$key] = ['chat_status' => 3, 'product_id' => $val['product_id']];
        }
        $builder = $this->db->table('product_offer');
        $builder->updateBatch($data, 'product_id');

        //상품 완료된거 제외하고 삭제로 표시
        $builder = $this->db->table('product')
            ->set('status', 4)
            ->set('deleted_at', 'now()', false)
            ->where('mem_idx', $mem_id)
            ->where('status <=', 2);
        $builder->update();
    }

    /**
     * chat push get
     */
    public function get_push_token($room_id)
    {
        return  $this->db->table('product_offer po')
            ->join('product p', 'po.product_id = p.`product_id` ', 'inner')
            ->select('p.mem_idx onner_id, po.dealer_id, po.bell_status_onner, po.bell_status_dealer, po.chat_status, po.chat_status_dealer,
                        (SELECT device_id FROM member_push_toekn WHERE p.`mem_idx` = member_id LIMIT 1) onner_device_token ,
                        (SELECT TYPE FROM member_push_toekn WHERE p.`mem_idx` = member_id LIMIT 1) onner_fcm_type ,
                        (SELECT device_id FROM member_push_toekn WHERE po.`dealer_id` = member_id LIMIT 1) dealer_device_token,
                        (SELECT TYPE FROM member_push_toekn WHERE po.`dealer_id` = member_id LIMIT 1) dealer_fcm_type', false)
            ->where('po.`offer_hash_id`', $room_id)
            ->get()->getRowArray();
    }

    public function member_alarm_in($data)
    {
        $whereIn = [];
        if ($data['onner_id'] > 0) array_push($whereIn, $data['onner_id']);
        if ($data['dealer_id'] > 0) array_push($whereIn, $data['dealer_id']);
        return $this->db->table('`member_alarm` ma ')
            ->whereIn('member_id', $whereIn)
            ->get()->getResultArray();
        // log_message('error', $this->db->lastQuery);        
    }

    public function update_last_message($room_id, $last_message, $last_message_type)
    {
        $build = $this->db->table('product_offer');
        $build
            ->set('last_message', $last_message)
            ->set('last_message_type', $last_message_type)
            ->set('updated_at', 'now()', false)
            ->where('offer_hash_id', $room_id)
            ->update();
    }

    /**
     * target_id = 보내려는 상대방 id
     * onner_id = 판매자
     * dealer_id =  구매자
     * ban_id 와 같은 쪽이 받는자 기준으로 밴이 되었는지 확인
     */
    public function user_ban_list_to_push($target_id, $onner_id, $dealer_id)
    {
        $ban_id = 0;
        if ($target_id == $onner_id) {
            //받는 사람 반대쪽... 즉 보내는 사람
            $ban_id = $dealer_id;
        } else {
            $ban_id = $onner_id;
        }


        $db = db_connect();
        $result = $db->table('member_user_ban')
            ->where('member_id', $target_id)
            ->where('ban_id', $ban_id)
            ->get()->getResultArray();
        // log_message('error', $this->db->lastQuery);
        // log_message('error', json_encode($result));

        if ($result) {
            return ["code" => 200, "message" => "list ok", "data" => $result];
        } else {
            return ["code" => 404, "message" => "not row data", "data" => []];
        }
    }

    public function all_push_token($member_id)
    {
        $db = db_connect();
        return $db->table('member_push_toekn')->where('member_id', $member_id)->get()->getResultArray();
    }
}
