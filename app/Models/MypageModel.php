<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class MypageModel extends Model
{
    protected $table = 'member';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    public function MykeywordsAdd($mem_id, $keyword)
    {
        $db = db_connect();
        $builder = $db->table('member_keywords');
        $result = $builder->selectCount('*', 'cnt')->where("member_id", $mem_id)->where("keyword", $keyword)->get()->getRowArray();

        if ($result['cnt'] > 0) {
            return ["code" => 301, "message" => false, "data" => []];
        }

        $state = $builder->insert(["member_id" => $mem_id, "keyword" => $keyword]);
        return ["code" => 200, "message" => $state, "data" => []];
    }

    public function MykeywordsDel($mem_id, $keyword)
    {
        $db = db_connect();
        $builder = $db->table('member_keywords');
        $state = $builder->delete(["member_id" => $mem_id, "keyword" => $keyword]);
        return ["code" => 200, "message" => $state, "data" => []];
    }

    public function MykeywordsList($mem_id)
    {
        $db = db_connect();
        $builder = $db->table('member_keywords')->select('keyword,created_at')->where('member_id', $mem_id);
        $data = $builder->get()->getResultArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    public function GetMemberGEO($mem_id)
    {
        $db = db_connect();
        $builder = $db->table('member_geo')->select('member_geo_id, longitude, latitude, station1, station2, created_at, selected ')->where('member_id', $mem_id);
        $data = $builder->get()->getResultArray();
        if (count($data) > 0)
            return ["code" => 200, "message" => "list ok", "data" => $data];
        else
            return ["code" => 200, "message" => "list ok", "data" => []];
    }

    public function MyAlaramList($mem_id)
    {
        $db = db_connect();
        $builder = $db->table('member_alarm')->select('*')->where('member_id', $mem_id);
        $data = $builder->get()->getRowArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    public function MyAlaramUpdate($mem_id, $data)
    {

        $db = db_connect();
        $builder = $db->table('member_alarm')->where('member_id', $mem_id)->update([
            'new_message' => $data['new_message'],
            'announcements' => $data['announcements'],
            'other' => $data['other'],
            'keyword' => $data['keyword'],
            'init_setting' => 1 //한번이라도 업데이트하면 1로 설정
        ]);
        return ["code" => 200, "message" => "update ok", "data" => $data];
    }

    /**
     * 구매완료 리스트 > 우선 판매자가 구매완료를 해야 리스트가 생긴다
     *  구매자의 정의는 물건을 산 사람입장이며 판매자에서는 판매목록(MySale)에만 나타난다
     * 
     * 
     */
    public function MyPurchase($mem_id, $page)
    {
        $db = db_connect();
        $builder = $db->table('product_purchase pp')
            ->select('p.product_id, p.category_code_id, p.product_name, p.status, p.updated_at, p.offer_yn, pt.img_url, pp.price
                    , (SELECT reviews_id FROM `reviews` WHERE product_id = p.product_id AND  reviewer_id = pp.onner_id)  review_onner_id
                    , (SELECT reviews_id FROM `reviews` WHERE product_id = p.product_id AND  reviewer_id = pp.dealer_id ) review_dealer_id
                    , (SELECT hash_id FROM `member` WHERE member_id = pp.onner_id ) onner_hash_id
                ', false)
            ->join('product p', 'pp.product_id = p.product_id', 'inner')
            ->join('product_thumbnail pt', 'pt.product_id = p.product_id', 'inner')
            ->where('pt.is_first', 1)
            ->where('pp.dealer_id', $mem_id);
        $per_page = 30;
        if (is_numeric($page) == false) $page = 1;
        if ($page <= 0) $page = 1;
        $offset = ($page - 1) * $per_page;
        $builder->orderBy("p.created_tick", "DESC");
        $builder->limit($per_page, $offset);
        $data = $builder->get()->getResultArray();

        log_message('info', $this->db->lastQuery);

        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    /**
     * 판매 중 이거나 판매완료 상품
     */
    public function MySale(int $mem_id, $page)
    {

        $db = db_connect();
        $builder = $db->table('product p')
            ->select('p.product_id, p.category_code_id, p.product_name, p.status, p.updated_at, p.offer_yn, pt.img_url, p.price, p.offer_price
                    , p.product_latitude, p.product_longitude, p.station
                    , (SELECT po.offer_hash_id FROM `product_offer` po LEFT JOIN offer_room_info ori ON po.product_offer_id = ori.product_offer_id WHERE po.product_id = p.product_id AND ori.transaction_code >= 2 LIMIT 1) offer_hash_id
                    , (SELECT reviews_id FROM `reviews` WHERE product_id = p.product_id AND  reviewer_id = p.mem_idx )  review_onner_id
                    , (SELECT reviews_id FROM `reviews` WHERE product_id = p.product_id AND  reviewer_id != p.mem_idx ) review_dealer_id
                    , (SELECT hash_id FROM `member` WHERE member_id = p.mem_idx ) onner_hash_id
                    ', false)
            ->join('product_thumbnail pt', 'pt.product_id = p.product_id', 'inner')
            ->where('pt.is_first', 1)
            ->where('p.mem_idx', $mem_id)
            ->whereIn('p.status', [1, 2, 3]); //sale , revered, sold out
        $per_page = 30;
        if (is_numeric($page) == false) $page = 1;
        if ($page <= 0) $page = 1;
        $offset = ($page - 1) * $per_page;
        $builder->orderBy("p.product_id", "DESC");
        $builder->limit($per_page, $offset);
        $data = $builder->get()->getResultArray();
        //log_message('info', $this->db->lastQuery);
        //echo $this->db->lastQuery;
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    /**
     * 상품 리스트 업 대기중 posts
     */
    public function MyPosts($mem_id, $page)
    {

        $db = db_connect();
        $builder = $db->table('product p')
            ->select('p.product_id, p.category_code_id, p.product_name, p.status, p.updated_at, p.offer_yn, p.price, p.offer_price, pt.img_url')
            ->join('product_thumbnail pt', 'pt.product_id = p.product_id', 'left')
            ->where('mem_idx', $mem_id)
            ->where('status', 0);
        $per_page = 30;

        if (is_numeric($page) == false) $page = 1;
        if ($page <= 0) $page = 1;
        $offset = ($page - 1) * $per_page;
        $builder->orderBy("p.product_id", "DESC");
        $builder->limit($per_page, $offset);
        $data_product = $builder->get()->getResultArray(); //일단 다뽑아
        $data = [];
        $unique_key = 'product_id';
        $tmp_key[] = array();
        foreach ($data_product as $key => &$item) {
            if (is_array($item) && isset($item[$unique_key])) {
                if (in_array($item[$unique_key], $tmp_key)) {
                    unset($data_product[$key]);
                } else {
                    $tmp_key[] = $item[$unique_key];
                }
            }
        }

        $i = 0;
        foreach ($data_product as $key => $val) {
            $data[$i] = $val;
            $i++;
        }

        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    /**
     * 미등록 상품 리스트 업
     */
    public function MyPostsOpen($mem_id, $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product')->where('mem_idx', $mem_id)->where('product_id', $product_id);
        $builder->set('status', 1);
        $obj = $builder->update();
        return ["code" => 200, "message" => "list ok", "data" => $obj];
    }

    public function MyPostsDel(int $mem_id, array $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product')->set('status', 4)->where('mem_idx', $mem_id)->whereIn('product_id', $product_id); //삭제로 업데이트
        $obj = $builder->update();

        // true next img delete
        return ["code" => 200, "message" => "deleted posts", "data" => $obj];
    }

    /**
     * 나의 위시 상품
     */
    public function MyWishList(int $mem_id, int $page)
    {
        $db = db_connect();
        $builder = $db->table('product p')
            ->select(' *, 
                (select count(*) from wishlist where product_id =  p.product_id ) wish_cnt,
                (select count(*) from product_offer where product_id =  p.product_id and chat_status = 1 ) chat_cnt  ', false)
            ->join('product_thumbnail pt', 'p.product_id = pt.product_id', 'inner')
            ->join('wishlist ws', 'p.product_id = ws.product_id', 'inner')
            ->where('pt.is_first', 1)
            ->where('ws.member_id', $mem_id);

        $builder->whereIn('status', [1, 2, 3]); //0 wait 1 selling 2 reserved 3 sold out 4 delete

        $per_page = 30;
        if (is_numeric($page) == false) $page = 1;
        if ($page <= 0) $page = 1;
        $offset = ($page - 1) * $per_page;
        $builder->orderBy("p.product_id", "DESC");
        $builder->limit($per_page, $offset);

        $product = $builder->get()->getResultArray();

        $builder = $db->table('wishlist ws')
            ->join('product p', 'p.product_id = ws.product_id', 'inner')
            ->join('product_thumbnail pt', 'pt.product_id = p.product_id', 'inner')
            ->where('pt.is_first', 1)
            ->where('ws.member_id', $mem_id);
        $wish = $builder->get()->getResultArray();

        //내가 추가한 위시 리스트 체크
        foreach ($product as $key => $val) {
            $product[$key]['i_wish'] = false; //기본 false 
            foreach ($wish as $k => $v) {
                if ($val['product_id'] == $v['product_id']) {
                    $product[$key]['i_wish'] = true;
                }
            }
        }

        return ["code" => 200, "message" => "list ok", "data" => $product];
    }

    /**
     * 구매자의 리뷰
     */
    public function ReviewProc(array $data)
    {

        $db = db_connect();

        //거래 정보 가져오기
        $builder = $db->table('product_purchase')->where('product_id', $data['product_id'])->limit(1);
        $product_data = $builder->get()->getRowArray();

        if ($product_data['onner_id'] == $data['reviewer_id']) {
            //판매자는 리뷰를 작성 할수없음
            return ["code" => 477, "message" => "The owner cannot write a review!", "data" => []];
        }

        if ($product_data['dealer_id'] != $data['reviewer_id']) {
            //실 구매자만 리뷰 작성 할수 있음
            return ["code" => 478, "message" => "The sale only write a review!", "data" => []];
        }

        //리뷰어 아이디가 같은게 있으면 안됨
        $builder = $db->table('reviews')->where('product_id', $data['product_id'])->where('reviewer_id', $data['reviewer_id'])->limit(1);
        $review_data = $builder->get()->getRowArray();

        if ($review_data) {
            //이미 리뷰가 작성되었습니다
            return ["code" => 479, "message" => "Already registered reviews!", "data" => []];
        }

        $data['target_id'] = $product_data['onner_id'];
        $data['type'] = 2;

        $builder = $db->table('member')->where('member_id', $product_data['onner_id'])->limit(1);
        $member_data = $builder->get()->getRowArray();
        $data['onner_hash_id'] = $member_data['hash_id'];

        $builder = $db->table('reviews');
        $insert_check =  $builder->insert($data);

        //var_dump($this->db->insertID());
        if (!$insert_check) {
            return ["code" => 489, "message" => "Registered reviews fail", "data" => []];
        }
        $data['reviews_id'] =  $this->db->insertID();

        //리뷰 작성 포인트 계산
        //point /10   + goodchoise * 0.5  - badchoise * 0.5
        $good_choice = 0;

        if ($data['good_choice']) {
            $good_choice  = count(explode(',', $data['good_choice'])) / 2;
        }

        $bad_choice = 0;
        if ($data['bad_choice']) {
            $bad_choice  = count(explode(',', $data['bad_choice'])) / 2;
        }

        if ($data['star_point'] > 0)
            $get_point = ($data['star_point']) + $good_choice - $bad_choice;
        else
            $get_point = $good_choice - $bad_choice;

        //구매자가 리뷰 해서 판매자 포인트 겟
        $builder = $db->table('member_badges_log');
        $builder->insert([
            'member_id' => $product_data['onner_id'],
            'title' => 'sale review posts',
            'get_point' => $get_point
        ]);

        $builder = $db->table('member_badges');
        $builder->set('get_point', 'get_point + ' . $get_point, false);
        $builder->where('member_id', $product_data['onner_id']);
        $builder->update();
        //echo $this->db->lastQuery;

        return ["code" => 200, "message" => "update", "data" => $data];
    }

    /**
     * 판매자의 리뷰
     */
    public function ReviewOnnerProc(array $data)
    {

        $db = db_connect();

        //거래 정보 가져오기
        $builder = $db->table('product_purchase')->where('product_id', $data['product_id'])->limit(1);
        $product_data = $builder->get()->getRowArray();

        if ($product_data['onner_id'] != $data['reviewer_id']) {
            //판매자가 아니면 리뷰를 작성 할수 없음
            return ["code" => 477, "message" => "The only owner write a review!", "data" => []];
        }

        //리뷰어 아이디가 같은게 있으면 안됨
        $builder = $db->table('reviews')->where('product_id', $data['product_id'])->where('reviewer_id', $data['reviewer_id'])->limit(1);
        $review_data = $builder->get()->getRowArray();

        if ($review_data) {
            //이미 리뷰가 작성되었습니다
            return ["code" => 479, "message" => "Already registered reviews!", "data" => []];
        }

        $data['target_id'] = $product_data['dealer_id'];
        $data['type'] = 1;


        $builder = $db->table('member')->where('member_id', $product_data['onner_id'])->limit(1);
        $member_data = $builder->get()->getRowArray();
        $data['onner_hash_id'] = $member_data['hash_id'];

        $builder = $db->table('reviews');
        $insert_check = $builder->insert($data);

        if (!$insert_check) {
            return ["code" => 489, "message" => "Registered reviews fail", "data" => []];
        }
        $data['reviews_id'] =  $this->db->insertID();

        //리뷰 작성 포인트 계산
        //point /10   + goodchoise * 0.5  - badchoise * 0.5
        $good_choice = 0;

        if ($data['good_choice']) {
            $good_choice  = count(explode(',', $data['good_choice'])) / 2;
        }

        $bad_choice = 0;
        if ($data['bad_choice']) {
            $bad_choice  = count(explode(',', $data['bad_choice'])) / 2;
        }

        if ($data['star_point'] > 0)
            $get_point = ($data['star_point']) + $good_choice - $bad_choice;
        else
            $get_point = $good_choice - $bad_choice;

        //판매자가 리뷰해서 포인트 겟
        $builder = $db->table('member_badges_log');
        $builder->insert([
            'member_id' => $product_data['dealer_id'],
            'title' => 'answer review posts',
            'get_point' => $get_point
        ]);

        $builder = $db->table('member_badges');
        $builder->set('get_point', 'get_point + ' . $get_point, false);
        $builder->where('member_id', $product_data['dealer_id']);
        $builder->update();
        return ["code" => 200, "message" => "update", "data" => $data];
    }

    public function product_info(int $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product')->where('product_id', $product_id)->limit(1);
        return $builder->get()->getRowArray();
    }

    public function product_all_list()
    {
        $db = db_connect();
        $builder = $db->table('product');
        return $builder->get()->getResultArray();
    }

    public function info_edit(array $data,  array $where)
    {
        $this->db->table('member')->update($data, $where);
    }

    public function my_product_status_update($member_id, $product_id, $status)
    {
        $db = db_connect();

        $builder = $db->table('product');
        $builder->where('product_id', $product_id);
        $product_data = $builder->get()->getRowArray();

        log_message('info', $this->db->lastQuery);

        if (!$product_data) {
            return ["code" => 404, "message" => "product not row data"];
        }

        if ($product_data["mem_idx"] != $member_id) {
            return ["code" => 408, "message" => "not math owner id"];
        }

        $builder = $db->table('product');
        $builder->set('status', $status); //0 wait 1 selling 2 reserved 3 sold out 4 delete
        $builder->where('product_id', $product_data['product_id']);
        $result = $builder->update();
        if ($result) {
            //오너가 상품 변경 했을대 알림 추가

            $builder = $db->table('product_offer')->where('product_id', $product_data['product_id'])->whereIn('chat_status_dealer', [0, 1, 2]);
            $product_offer = $builder->get()->getResultArray();

            $builder = $db->table('member_alerts');
            foreach ($product_offer as $key => $val) {
                $builder->insert([
                    'member_id' => $val['dealer_id'], //구매자들에게 변경 사실 안내
                    'message' => 'change product status !',
                    'type' => 5
                ]);
            }

            return ["code" => 200, "message" => "update ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function alerts($member_id)
    {
        $db = db_connect();
        $builder = $db->table('member_alerts ma')->where('ma.member_id', $member_id);
        return $builder->get()->getResultArray();
    }

    public function alerts_read($member_id)
    {
        $db = db_connect();
        $status = $db->table('member_alerts')
            ->set('is_read', 2)
            ->where('member_id', $member_id)->update();
        if ($status) {
            return ["code" => 200, "message" => "update ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function alerts_read_select($member_id, $member_alerts_id)
    {
        $db = db_connect();
        $status = $db->table('member_alerts')
            ->set('is_read', 2)
            ->where('member_id', $member_id)->update();
        if ($status) {
            return ["code" => 200, "message" => "update ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function alerts_del($member_id, $alerts_id)
    {
        $member_alerts_id = explode(",", $alerts_id);
        $db = db_connect();
        $result = $db->table('member_alerts')->where('member_id', $member_id)->whereIn('member_alerts_id', $member_alerts_id)->delete();
        if ($result) {
            return ["code" => 200, "message" => "delete ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function keyword_alerts($member_id)
    {
        $db = db_connect();
        $builder = $db->table('member_alerts_keywords mak')
            ->select('pt.img_url, mak.product_id, mak.message, mak.keyword, mak.member_alerts_keywords_id, mak.is_read, mak.created_at', false)
            ->join('product_thumbnail pt', 'mak.product_id = pt.product_id', 'inner')
            ->where('pt.is_first', 1)
            ->where('mak.member_id', $member_id);
        return $builder->get()->getResultArray();
    }

    public function keyword_alerts_read($member_id)
    {
        $db = db_connect();
        $status = $db->table('member_alerts_keywords')
            ->set('is_read', 2)
            ->where('member_id', $member_id)->update();
        if ($status) {
            return ["code" => 200, "message" => "update ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function keyword_alerts_del($member_id, $alerts_keywords_id)
    {
        $member_alerts_keywords_id = explode(",", $alerts_keywords_id);
        $db = db_connect();
        $result = $db->table('member_alerts_keywords')->where('member_id', $member_id)->whereIn('member_alerts_keywords_id', $member_alerts_keywords_id)->delete();
        //echo $this->db->lastQuery; exit;
        if ($result) {
            return ["code" => 200, "message" => "delete ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function user_ban_list($member_id)
    {
        $db = db_connect();
        $result = $db->table('member_user_ban mub')
            ->select('m.hash_id, m.name, mub.status')->where('mub.member_id', $member_id)
            ->join('member m', 'mub.ban_id = m.member_id', 'inner')
            ->get()->getResultArray();
        if ($result) {
            return ["code" => 200, "message" => "list ok", "data" => $result];
        } else {
            return ["code" => 404, "message" => "not row data", "data" => []];
        }
    }

    public function user_ban_add($member_id, $ban_hash_id)
    {
        $db = db_connect();
        $ban_user_id = $db->table('member')->select('member_id')->where('hash_id', $ban_hash_id)->get()->getRowObject();

        if (!$ban_user_id) {
            return ["code" => 404, "message" => "not row data", "data" => []];
        }

        $ban_id = $db->table('member_user_ban')->select('ban_id')->where('ban_id', $ban_user_id->member_id)->get()->getRowObject();

        if ($ban_id) {
            return ["code" => 304, "message" => "duplicate data", "data" => []];
        }

        $data = ['member_id' => $member_id, 'ban_id' => $ban_user_id->member_id];
        $result = $db->table('member_user_ban')->insert($data);



        $sql = "SELECT po.product_offer_id, p.mem_idx FROM product p 
            INNER JOIN product_offer po ON p.product_id = po.product_id
            WHERE p.mem_idx = ? AND dealer_id = ?
            UNION  
            SELECT po.product_offer_id , p.mem_idx  FROM product p 
            INNER JOIN product_offer po ON p.product_id = po.product_id
            WHERE p.mem_idx = ? AND dealer_id = ?";
        $rows = $db->query($sql, [$member_id, $ban_user_id->member_id, $ban_user_id->member_id, $member_id])->getResultArray();
        foreach ($rows as $key => $val) {
            if ($val['mem_idx'] == $member_id) {
                //내가 판매자로 있는것들 오퍼
                $db->table('product_offer')->set('chat_status', 4)->where('product_offer_id', $val['product_offer_id'])->update();
            } else {
                //내가 구매자로 있는것들 오퍼
                $db->table('product_offer')->set('chat_status_dealer', 4)->where('product_offer_id', $val['product_offer_id'])->update();
            }
        }




        if ($result) {
            return ["code" => 200, "message" => "insert ok", "data" => $result];
        } else {
            return ["code" => 404, "message" => "insert error", "data" => []];
        }
    }


    public function user_ban_del($member_id, $ban_hash_id)
    {
        $db = db_connect();
        $ban_user_id = $db->table('member')->select('member_id')->where('hash_id', $ban_hash_id)->get()->getRowObject();

        if (!$ban_user_id) {
            return ["code" => 404, "message" => "not row data", "data" => []];
        }

        $result = $db->table('member_user_ban')->where('member_id', $member_id)->where('ban_id', $ban_user_id->member_id)->delete();
        if ($result) {
            return ["code" => 200, "message" => "del ok", "data" => $result];
        } else {
            return ["code" => 404, "message" => "del error", "data" => []];
        }
    }
}
