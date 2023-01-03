<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;


class ProductModel extends Model
{
    protected $table = 'product';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    /**
     * 상품리스트
     */
    public function product_list($top_input_search, $search_location, $page, $mem_id = 0, $category = 0, $min_price = -1, $max_price = -1, $latitude = '', $longitude = '', $period = 0, $compleate = 0, $is_free = null)
    {

        $db = db_connect();
        $builder = $db->table($this->table)
            ->select('product.product_id, product.category_code_id, product.product_name, product.price, product.views, product.status, product.offer_yn, 
                    product.product_latitude, product.product_longitude , product.updated_at, product.created_at,
                    pt.img_url, pt.is_first,
                (select count(*) from wishlist where product_id =  product.product_id ) wish_cnt,
                (select count(*) from product_offer where product_id =  product.product_id and chat_status != 4 ) chat_cnt
                , product.station, product.fix  ', false)
            ->join('product_thumbnail pt', 'product.product_id = pt.product_id', 'inner');
        $builder->where('pt.is_first =', '1', false);


        //검색어
        if ($top_input_search) {
            $builder->like('product.product_name', $top_input_search);
        }

        //카테고리가 있으면 필터
        if ($category > 0) {
            $builder->where('product.category_code_id', $category);
        }

        //프리 가격 선택하면 0원 만        
        if ($is_free && $is_free == "true") {
            $builder->where('product.price =', 0, false);
        } else {
            //최소값이 0보다 크면
            // -1이면 검색안함 무한
            if ($min_price >= 0) {
                $builder->where('product.price >=', $min_price, false);
            }

            //최대값이 0보다 크면
            // -1이면 검색안함 무한
            if ($max_price >= 0) {
                $builder->where('product.price <=', $max_price, false);
            }
        }

        //검색거리
        if ($search_location > 0 && $latitude && $longitude) {
            $builder->where("ST_DISTANCE(
                        GEOMFROMTEXT( 'POINT(" . $latitude . " " . $longitude . ")', 4326 ),
                        GEOMFROMTEXT( CONCAT('POINT(', product_latitude, ' ', product_longitude , ')'), 4326 )
                    ) * 111195 < " . $search_location, null, false);
        }

        //등록기간 기준
        if ($period > 0) {
            $builder->where('product.updated_at >=', 'DATE_ADD(NOW(), INTERVAL -' . $period . ' DAY)', false);
        }

        if ($compleate) {
            //완료 필터가 있으면 완료는 비노출
            $builder->whereIn('status', [1, 2]); //0 wait 1 selling 2 reserved 3 sold out 4 delete
        } else {
            $builder->whereIn('status', [1, 2, 3]); //0 wait 1 selling 2 reserved 3 sold out 4 delete
        }

        //$builder->orWhere('fix = ', '1' , false);
        $builder->orWhere(" ( fix = 1 AND status != 4)",  null, false);

        $per_page = 30; //to 50
        if (is_numeric($page) == false) $page = 1;
        if ($page <= 0) $page = 1;

        $offset = ($page - 1) * $per_page;
        $builder->orderBy("product.updated_at", "DESC");
        $builder->orderBy("product.product_id", "DESC");
        $builder->limit($per_page, $offset);
        $product = $builder->get()->getResultArray();
        //log_message(5, $this->db->lastQuery);




        if ($product) {
            foreach ($product as $key => $val) {
                if ($val['fix'] == 1) {
                    $product[$key]['station'] = "";
                }
            }
        }

        //log_message('info', $this->db->lastQuery);

        $wish = [];
        //회원만 위시리스트 검색
        if ($mem_id > 0) {
            $builder = $db->table('wishlist ws')
                ->join('product p', 'p.product_id = ws.product_id', 'inner')
                ->join('product_thumbnail pt', 'pt.product_id = p.product_id', 'inner')
                ->where('pt.is_first', 1)
                ->where('ws.member_id', $mem_id);
            $wish = $builder->get()->getResultArray();
        }

        //내가 추가한 위시 리스트 체크
        foreach ($product as $key => $val) {
            $product[$key]['i_wish'] = false; //기본 false 
            foreach ($wish as $k => $v) {
                if ($val['product_id'] == $v['product_id']) {
                    $product[$key]['i_wish'] = true;
                }
            }
        }

        return $product;
    }

    /**
     *  상품정보
     */
    public function product_info(int $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product')->where('product_id', $product_id)->limit(1);

        return $builder->get()->getRowArray();
    }

    /**
     * 
     */
    public function product_add(array $data)
    {
        $db = db_connect();

        $builder = $db->table('product')->selectCount('*', 'cnt')->where('mem_idx', (int)$data['mem_idx'])->whereIn('status', [1]);
        $count = $builder->get()->getRowArray();

        if ($count['cnt'] >= 30) {
            return false;
        }

        $builder = $db->table('product');
        $result = $builder->insert($data);

        if ($result) {
            // $builder            
            // ->set('upstream_at', 'now()', false)
            // ->where('product_id', $this->db->insertID())
            // ->update();
            return $this->db->insertID();
        } else {
            return false;
        }
    }

    public function product_modify_proc(array $data, int $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product');

        $result = $builder
            ->set($data)
            ->set('updated_at', 'now()', false)
            ->where('product_id', $product_id)
            ->update();

        if ($result) {
            return $product_id;
        } else {
            return false;
        }
    }

    public function product_chat_offer_price_update($data, $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product_offer')
            ->set("offer_price", $data['price'])
            ->set('offer_status', 0)   //상품변경에 의한 오퍼 상태값 초기화
            ->where('product_id', $product_id)
            ->update();
    }



    /**
     * 상품 삭제 [status 4로 업데이트]
     */
    public function product_delete_proc(int $product_id, int $member_id)
    {
        $db = db_connect();
        $builder = $db->table('product')
            ->set('status', 4)
            ->where('mem_idx', $member_id)
            ->where('product_id', $product_id);
        return $builder->update();
    }

    public function product_uv_update(int $product_id)
    {
        $db = db_connect();
        $builder = $db->table('product');
        $builder->set('views', 'views+1', false);
        $builder->where('product_id', $product_id);
        $builder->update();
        //echo $this->db->lastQuery;
    }

    public function product_wish_add(int $product_id, int $member_id)
    {
        $db = db_connect();
        $builder = $db->table('wishlist');
        $result = $builder->delete(["product_id" => $product_id, "member_id" => $member_id]);
        $result = $builder->insert(["product_id" => $product_id, "member_id" => $member_id]);
        //echo $this->db->lastQuery;

        if ($result) {
            return $this->db->insertID();
        } else {
            return false;
        }
        //echo $this->db->lastQuery;
    }

    public function product_wish_del(int $product_id, int $member_id)
    {
        $db = db_connect();
        $builder = $db->table('wishlist');
        $result = $builder->delete(["product_id" => $product_id, "member_id" => $member_id]);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function product_thumbnail_add(array $data)
    {
        $db = db_connect();
        $builder = $db->table('product_thumbnail');
        $builder->insertBatch($data);
    }

    public function product_thumbnail_del(array $data)
    {
        $db = db_connect();
        $builder = $db->table('product_thumbnail');
        $builder->whereIn('product_thumbnail_id', $data)->delete();
    }

    public function product_thumbnail_check($id)
    {
        $db = db_connect();
        $builder = $db->table('product_thumbnail');
        $product_thumbnail_data = $builder->where('product_id', $id)->orderBy('product_thumbnail_id', 'asc')->get()->getResultArray();
        $cnt = 0;
        $first_id = 0;
        foreach ($product_thumbnail_data as $key => $val) {
            if ($key  == 0) {
                $first_id = $val['product_thumbnail_id'];
            }
            if ($val['is_first'] == 1) {
                $cnt++;
            }
        }

        //대표썸네일이 지워진 상태 첫번째걸로 업데이트        
        if ($cnt == 0) {
            $builder = $db->table('product_thumbnail')
                ->set(['is_first' => 1])
                ->where('product_thumbnail_id', $first_id)
                ->update();
        }
    }


    public function product_detail_info(int $product_id, int $mem_id)
    {
        $db = db_connect();
        $result = [];
        $builder = $this->db->table('product_thumbnail pt')
            ->select('pt.product_thumbnail_id, pt.img_url, pt.is_first');
        if ($product_id) {
            $builder->where('pt.product_id', $product_id);
        }
        $result["product_thumbnail"] = $builder->get()->getResultArray();

        $product = $this->db->table($this->table)
            ->select(' (select count(*) from `wishlist` where `wishlist`.`product_id` = `product`.`product_id` ) saved_cnt, 
                       (select count(*) from `product_offer`  where `product_offer`.`product_id` = `product`.`product_id` and chat_status != 2 ) chat_cnt,
                       (SELECT count(*) review_cnt FROM `reviews` WHERE product_id = product.product_id AND reviewer_id != product.mem_idx  LIMIT 1) review_cnt,
                       (SELECT  m.hash_id FROM `product_purchase` INNER JOIN member m ON m.member_id = product_purchase.`dealer_id` WHERE `product_purchase`.`product_id` = `product`.`product_id`) dealer_hash_id,
                       (select  category_sub_name from `category_code`  where `category_code`.`category_code_id` = `product`.`category_code_id`) category_name,
                       product.product_id, product.category_code_id, product.product_name, product.price, product.views, product.status, product.offer_yn, 
                       product.product_latitude, product.product_longitude , product.updated_at, product.created_at, product.mem_idx, product.content, product.shares , product.station, product.fix,
                       (select pf_location from `product_preference` where `product_preference`.`product_id` = `product`.`product_id` ) pf_location, 
                       (select pf_latitude from `product_preference` where `product_preference`.`product_id` = `product`.`product_id` ) pf_latitude,
                       (select pf_longitude from `product_preference` where `product_preference`.`product_id` = `product`.`product_id` ) pf_longitude
                       ', false)
            ->where("product.product_id", $product_id)
            ->get()->getRowArray();

        $sql = "SELECT  transaction_code, offer_hash_id  FROM `product_offer` po 
        LEFT JOIN offer_room_info ori ON po.product_offer_id = ori.product_offer_id 
        WHERE po.product_id = ? AND ori.transaction_code >= 2";
        $result["offer_info"] = $this->db->query($sql, [$product_id])->getResultArray();


        //위시리스트
        $builder = $db->table('wishlist ws')
            ->join('product p', 'p.product_id = ws.product_id', 'inner')
            ->join('product_thumbnail pt', 'pt.product_id = p.product_id', 'inner')
            ->where('pt.is_first', 1)
            ->where('ws.member_id', $mem_id);
        $wish = $builder->get()->getResultArray();

        //내가 추가한 위시 리스트 체크        
        $product['i_wish'] = false; //기본 false 
        foreach ($wish as $k => $v) {
            if ($product['product_id'] == $v['product_id']) {
                $product['i_wish'] = true;
            }
        }

        $result["product"] = $product;

        $p_mem_idx = $product["mem_idx"] ?? 0;
        $sql = 'SELECT `hash_id`,`sns_type`,`name`,`email`,`picture`,        
        `mb`.`get_point`,
        `bd`.`badges_image`, `bd`.`badges_title`
        FROM `member` m         
        LEFT JOIN `member_badges` mb ON m.member_id = mb.member_id
        LEFT JOIN `badges` bd ON bd.badges_id = mb.badges_id
        WHERE m.member_id = ?';
        $result["sale_member_info"] = $this->db->query($sql, [$p_mem_idx])->getRowArray();

        return $result;
    }


    /**
     * 상품 체크
     */
    public function offer_check(int $product_id, int $offer_price)
    {
        $result["product"] = $this->db->table($this->table)
            ->where("product.product_id", $product_id)
            ->get()->getRowArray();
        $code = 200;
        $message = "";

        if ($result["product"]) {
            if ($result["product"]["offer_yn"] == 0) {
                //오퍼 불가능 옵션 선택
                $code = 405;
            }
        } else {
            //err not math row
            $code = 404;
        }

        if ($offer_price == $result["product"]["price"] &&  $code == 405) {
            $code = 200;
        }

        $result["code"] = $code;
        $result["message"] = $message;

        return $result;
    }

    /**
     * make offer add
     */
    public function order_offer_add(array $data)
    {
        $builder = $this->db->table('product_offer');
        $builder->insert($data);
        if ($this->db->affectedRows() > 0) {
            //ok            
            return ["code" => 200, "message" => "insert ok"];
        } else {
            //fail
            return ["code" => 406, "message" => "insert fail"];
        }
    }


    public function order_owner_check(array $data)
    {
        // 해당 주문 번호와 오너가 일치하는지 검증

    }


    public function product_offer_list(int $mem_id, int $page)
    {

        $sql = "SELECT po.offer_hash_id, po.product_id, po.offer_price, offer_status, bell_status_onner, bell_status_dealer, chat_status, last_message, last_message_type, mem_idx, po.updated_at, po.created_at
            ,(SELECT picture FROM `member` WHERE member_id = po.dealer_id) dealer_picture 
            ,(SELECT hash_id FROM `member` WHERE member_id = po.dealer_id) dealer_hash_id 
            ,(SELECT name FROM `member` WHERE member_id = po.dealer_id) dealer_name
            ,(SELECT picture FROM `member` WHERE member_id = p.mem_idx) owner_picture 
            ,(SELECT hash_id FROM `member` WHERE member_id = p.mem_idx) owner_hash_id 
            ,(SELECT name FROM `member` WHERE member_id = p.mem_idx) owner_name
            ,(SELECT img_url FROM `product_thumbnail` WHERE product_id = po.product_id AND is_first = 1) product_thumbnail
            ,(SELECT transaction_code FROM `offer_room_info` WHERE product_offer_id = po.product_offer_id) transaction_code
        FROM `product_offer` po 
        INNER JOIN `product` p ON po.product_id = p.`product_id`
        WHERE p.mem_idx = ? AND po.chat_status in(0,1,2,5) 
        "; //exit, delete 상태만 제외  
        //AND po.chat_status_dealer in(0,1,2,5)
        $onner_list = $this->db->query($sql, [$mem_id])->getResultArray();
        // log_message('notice', 'onner_q');
        // log_message('notice', $this->db->lastQuery);

        $sql = "SELECT po.offer_hash_id, po.product_id, po.offer_price, offer_status, bell_status_onner, bell_status_dealer, chat_status, last_message, last_message_type, mem_idx, po.updated_at, po.created_at
            ,(SELECT picture FROM `member` WHERE member_id = po.dealer_id) dealer_picture 
            ,(SELECT hash_id FROM `member` WHERE member_id = po.dealer_id) dealer_hash_id 
            ,(SELECT name FROM `member` WHERE member_id = po.dealer_id) dealer_name
            ,(SELECT picture FROM `member` WHERE member_id = p.mem_idx) owner_picture 
            ,(SELECT hash_id FROM `member` WHERE member_id = p.mem_idx) owner_hash_id 
            ,(SELECT name FROM `member` WHERE member_id = p.mem_idx) owner_name
            ,(SELECT img_url FROM `product_thumbnail` WHERE product_id = po.product_id AND is_first = 1) product_thumbnail
            ,(SELECT transaction_code FROM `offer_room_info` WHERE product_offer_id = po.product_offer_id) transaction_code
        FROM `product_offer` po 
        INNER JOIN `product` p ON po.product_id = p.`product_id`
        WHERE po.dealer_id = ? AND po.chat_status_dealer in(0,1,2,5)
        ";
        $dealer_list = $this->db->query($sql, [$mem_id])->getResultArray();
        $result["lists"] = array_merge($onner_list,  $dealer_list);

        // log_message('notice', 'dealer_q');
        // log_message('notice', $this->db->lastQuery);

        foreach ($result["lists"] as $key => $val) {
            $result["lists"][$key]["is_own"] = false;
            if ($val["mem_idx"] == $mem_id) $result["lists"][$key]["is_own"] = true;
        }

        usort($result['lists'], function ($a, $b) {
            return new \DateTime($a['updated_at']) < new \DateTime($b['updated_at']);
        });

        return $result;
    }

    /**
     * 오퍼 허용
     */
    public function offer_accept($member_id, $room_id)
    {

        $db = db_connect();

        $builder = $db->table('product')->join('product_offer', 'product_offer.product_id = product.product_id');
        $builder->where('offer_hash_id', $room_id);
        $product_data = $builder->get()->getRowArray();

        if (!$product_data) {
            return ["code" => 404, "message" => "not row data"];
        }

        if ($product_data["mem_idx"] != $member_id) {
            return ["code" => 408, "message" => "not math owner id"];
        }

        $builder = $db->table('product_offer');
        // $builder->set('chat_status', 1); // 0 wait 1 accept 2 reject 3 exit 4 delete
        // $builder->set('chat_status_dealer', 1); // 0 wait 1 accept 2 reject 3 exit 4 delete
        $builder->set('offer_status', 1);
        $builder->where('offer_hash_id', $room_id);
        $result = $builder->update();


        if ($result) {
            return ["code" => 200, "link" => '/chat/chat_room/' . $room_id];
        } else {
            return ["code" => 500];
        }
    }

    /**
     * 오퍼 거절
     */
    public function offer_reject($member_id, $room_id)
    {

        $db = db_connect();

        $builder = $db->table('product')->join('product_offer', 'product_offer.product_id = product.product_id');
        $builder->where('offer_hash_id', $room_id);
        $product_data = $builder->get()->getRowArray();

        if (!$product_data) {
            return ["code" => 404, "message" => "not row data"];
        }

        if ($product_data["mem_idx"] != $member_id) {
            return ["code" => 408, "message" => "not math owner id"];
        }

        $builder = $db->table('product_offer');
        // $builder->set('chat_status', 2); // 0 wait 1 accept 2 reject 3 exit 4 delete
        // $builder->set('chat_status_dealer', 2); // 0 wait 1 accept 2 reject 3 exit 4 delete
        $builder->set('offer_status', 2);
        $builder->where('offer_hash_id', $room_id);
        $result = $builder->update();

        // $builder = $db->table('product');
        // $builder->set('status', 1); //0 wait 1 selling 2 reserved 3 sold out 4 delete
        // $builder->where('product_id', $product_data['product_id']);
        // $result = $builder->update();

        if ($result) {
            return ["code" => 200, "link" => '/chat/chat_room/' . $room_id];
        } else {
            return ["code" => 500];
        }
    }

    /**
     * 거래완료
     */
    public function offer_finish($member_id, $room_id)
    {

        $db = db_connect();

        $builder = $db->table('product')->join('product_offer', 'product_offer.product_id = product.product_id');
        $builder->where('offer_hash_id', $room_id);
        $product_data = $builder->get()->getRowArray();

        if (!$product_data) {
            return ["code" => 404, "message" => "not row data"];
        }

        if ($product_data["mem_idx"] != $member_id) {
            return ["code" => 408, "message" => "not math owner id"];
        }

        $builder = $db->table('product_offer');
        $builder->set('chat_status', 5); // 0 wait 1 accept 2 reject 3 exit 4 delete 5  finish
        $builder->set('chat_status_dealer', 5); // 0 wait 1 accept 2 reject 3 exit 4 delete  5  finish
        $builder->where('offer_hash_id', $room_id);
        $result = $builder->update();

        $builder = $db->table('product');
        $builder->set('status', 3); //0 wait 1 selling 2 reserved 3 sold out 4 delete
        if ($product_data['offer_price'] > 0 && $product_data['offer_status'] == 1) $builder->set('offer_price',  $product_data['offer_price']);
        $builder->where('product_id', $product_data['product_id']);
        $result = $builder->update();

        //해당 챗방 거래 상태 업데이트
        $db->table('offer_room_info')->delete(['product_offer_id' => $product_data['product_offer_id']], 1);
        $db->table('offer_room_info')->insert(['product_offer_id' => $product_data['product_offer_id'], 'transaction_code' => 3]);

        $price = $product_data['price'];
        //offer 한 가격있으면 해당 가격으로 판매가 결정
        //offer_status 1 수락
        if ($product_data['offer_price'] > 0 && $product_data['offer_status'] == 1) $price = $product_data['offer_price'];

        $builder = $db->table('product_purchase');
        $result = $builder->insert(['product_id' => $product_data['product_id'], 'onner_id' => $product_data['mem_idx'], 'dealer_id' => $product_data['dealer_id'], 'price' => $price]);


        $builder = $db->table('product_offer')->where('product_id', $product_data['product_id'])->whereIn('chat_status_dealer', [0, 1, 2]);
        $product_offer = $builder->get()->getResultArray();

        $builder = $db->table('member_alerts');
        foreach ($product_offer as $key => $val) {
            $builder->insert([
                'member_id' => $val['dealer_id'], //구매자들에게 변경 사실 안내
                'message' => $product_data['product_name'] . ' sold out !',
                'type' => 5
            ]);
        }

        if ($result) {
            return ["code" => 200, "message" => "finish thank you"];
        } else {
            return ["code" => 500];
        }
    }

    /**
     *  상품 Selling / Reserved / Sold  상태변경
     *   예약 --
     */
    public function product_status_update($member_id, $room_id, $status)
    {
        $db = db_connect();

        $builder = $db->table('product')->join('product_offer', 'product_offer.product_id = product.product_id');
        $builder->where('offer_hash_id', $room_id);
        $product_data = $builder->get()->getRowArray();

        $builder = $db->table('product_offer')->where('product_id', $product_data['product_id'])->whereIn('chat_status_dealer', [0, 1, 2]);
        $product_offer = $builder->get()->getResultArray();

        //echo $this->db->lastQuery;

        if (!$product_data) {
            return ["code" => 404, "message" => "product not row data"];
        }

        if ($product_data["mem_idx"] != $member_id) {
            return ["code" => 408, "message" => "not math owner id"];
        }

        $this->db->transStart();
        if ($status == 1) {
            //셀링으로 바꿀경우 예약데이터는 삭제
            $db->table('offer_room_info')->delete(['product_offer_id' => $product_data['product_offer_id']], 1);
        }
        if ($status == 2) {
            $builder = $db->table('product_offer');
            $builder->set('chat_status', $status);
            $builder->set('chat_status_dealer', $status);
            $builder->where('offer_hash_id', $room_id);
            $result = $builder->update();

            $db->table('offer_room_info')->delete(['product_offer_id' => $product_data['product_offer_id']], 1);
            $db->table('offer_room_info')->insert(['product_offer_id' => $product_data['product_offer_id'], 'transaction_code' => $status]);
        }

        $builder = $db->table('product');
        $builder->set('status', $status); //0 wait 1 selling 2 reserved 3 sold out 4 delete
        $builder->where('product_id', $product_data['product_id']);
        $builder->update();



        $builder = $db->table('member_alerts');
        foreach ($product_offer as $key => $val) {
            $builder->insert([
                'member_id' => $val['dealer_id'], //구매자들에게 변경 사실 안내
                'message' => $product_data['product_name'] . ' status change !',
                'type' => 5
            ]);
        }


        $result = $this->db->transComplete();
        if ($result) {
            return ["code" => 200, "message" => "update ok"];
        } else {
            return ["code" => 404, "message" => "not row data"];
        }
    }

    public function offer_reserv_cancel($member_id, $room_id)
    {
        $db = db_connect();

        $builder = $db->table('product')->join('product_offer', 'product_offer.product_id = product.product_id');
        $builder->where('offer_hash_id', $room_id);
        $product_data = $builder->get()->getRowArray();

        if (!$product_data) {
            return ["code" => 404, "message" => "not row data"];
        }

        $message = "";
        $result = null;
        if ($product_data["mem_idx"] == $member_id) {
            //판매자가 예약 상태 걸수있음
            $result = $db->table('offer_room_info')->delete(['product_offer_id' => $product_data['product_offer_id']], 1);
        }

        if ($result) {
            return ["code" => 200, "message" => $message];
        } else {
            return ["code" => 404, "message" => "not fund member id miss update"];
        }
    }

    /**
     * 챗룸 상태값 변경
     * 수락 1, 거절 2, 나가기3, 삭제 4
     */
    public function offer_status_update($member_id, $room_id, $status)
    {
        $db = db_connect();

        $builder = $db->table('product')->join('product_offer', 'product_offer.product_id = product.product_id');
        $builder->where('offer_hash_id', $room_id);
        $product_data = $builder->get()->getRowArray();

        //삭제 상태가 아니라 로우 자체가 없는 경우
        if (!$product_data) {
            log_message('error',  'offer_status_update not product 404');
            return ["code" => 404, "message" => "not row data"];
        }

        $message = "";

        $this->db->transStart();
        // if($status == 2 && $product_data["mem_idx"] == $member_id) {
        //     //판매자가 예약 상태 걸수있음
        //     $db->table('offer_room_info')->delete(['product_offer_id'=>$product_data['product_offer_id']],1);
        //     $db->table('offer_room_info')->insert(['product_offer_id'=>$product_data['product_offer_id'], 'transaction_code'=>2]);
        // }

        if ($product_data["mem_idx"] == $member_id) {
            $message = "onner update";
            $builder = $db->table('product_offer');
            if ($status == 1) {
                $builder->set('offer_status', 1);
            } else if ($status == 2) {
                $builder->set('offer_price', 0); //거절이면 제시가격을 0원으로 > 즉 원래가격
                $builder->set('offer_status', 2);
            } else if ($status == 3 || $status == 4) {
                $builder->set('chat_status', $status);
            }

            $builder->set('updated_at', 'now()', false);
            $builder->where('offer_hash_id', $room_id);
            $result = $builder->update();
            // log_message('info',  $this->db->lastQuery);
        }

        if ($product_data["dealer_id"] == $member_id) {
            $message = "dealer update";
            $builder = $db->table('product_offer');

            if ($status == 3 || $status == 4) {
                $builder->set('chat_status_dealer', $status);
            }
            $builder->set('updated_at', 'now()', false);
            $builder->where('offer_hash_id', $room_id);
            $builder->update();
            // log_message('info',  $this->db->lastQuery);
        }

        //둘중 한명이라도 나가면 예약은 취소 상품은 셀링
        if ($status == 3 || $status == 4) {
            //셀링 예약 일때 만 판매완료는 제외
            if ($product_data['status'] <= 2) {
                $db->table('offer_room_info')->delete(['product_offer_id' => $product_data['product_offer_id']], 1);
                $db->table('product')->set('status', 1)->where('product_id', $product_data['product_id'])->update();
            }
        }

        //둘다 채팅방을 나가면 방 삭제
        if ($product_data["mem_idx"] == $member_id) {
            //판매자인데 상대 딜러가 나간경우 삭제
            if ($product_data['chat_status_dealer'] >= 3) {
                $builder = $db->table('product_offer');
                $builder->where('offer_hash_id', $room_id);
                $builder->delete();
            }
        }

        if ($product_data["dealer_id"] == $member_id) {
            //구매자인데 상대 판매자가 나간경우 삭제
            if ($product_data['chat_status'] >= 3) {
                $builder = $db->table('product_offer');
                $builder->where('offer_hash_id', $room_id);
                $builder->delete();
            }
        }

        $result = $this->db->transComplete();

        // log_message('info',  $this->db->lastQuery);
        if ($result) {
            return ["code" => 200, "message" => $message];
        } else {
            return ["code" => 404, "message" => "not fund member id miss update"];
        }
    }



    /**
     * 룸 정보
     */
    public function getProductFindRoomID($room_id)
    {
        $sql = "SELECT offer_hash_id, po.product_id, po.offer_price, offer_status, bell_status_onner, bell_status_dealer, chat_status, last_message, chat_status_dealer
            , p.product_name, p.price, p.offer_yn, p.status, p.product_latitude, p.product_longitude
            ,(SELECT picture FROM `member` WHERE member_id = po.dealer_id) dealer_picture 
            ,(SELECT hash_id FROM `member` WHERE member_id = po.dealer_id) dealer_hash_id 
            ,(SELECT name FROM `member` WHERE member_id = po.dealer_id) dealer_name
            ,(SELECT get_point FROM `member_badges` WHERE member_id = po.dealer_id LIMIT 1) dealer_green_point            
            ,(SELECT picture FROM `member` WHERE member_id = p.mem_idx) onner_picture 
            ,(SELECT hash_id FROM `member` WHERE member_id = p.mem_idx) onner_hash_id 
            ,(SELECT name FROM `member` WHERE member_id = p.mem_idx) onner_name
            ,(SELECT get_point FROM `member_badges` WHERE member_id = p.mem_idx LIMIT 1) onner_green_point
            ,(SELECT img_url FROM `product_thumbnail` WHERE product_id = po.product_id AND is_first = 1) product_thumbnail
            ,(SELECT reviews_id FROM `reviews` WHERE product_id = po.product_id AND  reviewer_id = po.dealer_id ) review_id
            ,(SELECT reviews_id FROM `reviews` WHERE product_id = po.product_id AND  reviewer_id = p.mem_idx ) review_onner_id
            ,(SELECT transaction_code FROM `offer_room_info` WHERE product_offer_id = po.product_offer_id) transaction_code
            , p.station
        FROM `product_offer` po 
        INNER JOIN `product` p ON po.product_id = p.`product_id`
        WHERE po.offer_hash_id = ?
        ";

        return $this->db->query($sql, [$room_id])->getRowArray();
    }

    /**
     * 등록된 자신의 geo 
     */
    public function getMyGeo(int $member_id)
    {
        $db = db_connect();
        $builder = $db->table("member_geo")->select('latitude, longitude, station1, station2, selected');
        $builder->where('member_id', $member_id)->where('selected', 1);

        $data = $builder->get()->getRowArray();
        $count = $this->db->affectedRows();;

        if ($count  > 0) {
            return ["code" => 200, "message" => 'list ok', "data" => $data];
        } else {
            return ["code" => 402, "message" => 'not register your geo', "data" => $data];
        }
    }

    public function getCategoryCode(int $cete_code)
    {
        $db = db_connect();
        $builder = $db->table("category_code");
        $builder->where('category_code_id', $cete_code);

        $data = $builder->get()->getRowArray();
        $count = $this->db->affectedRows();;

        if ($count  > 0) {
            return ["code" => 200, "message" => 'list ok', "data" => $data];
        } else {
            return ["code" => 402, "message" => 'not match cetegory coed', "data" => $data];
        }
    }

    public function neighborhood_list()
    {
        $db = db_connect();
        $builder = $db->table('aera a')
            ->select('a.aera_id, ag.aera_geo_id, a.aera_step1, ag.aera_step2, ag.latitude, ag.longitude ')
            ->join('aera_geo ag', 'a.aera_id = ag.aera_id')->orderBy('ag.aera_step2', 'asc', true);
        return $builder->get()->getResultArray();
    }

    public function chat_alram_personal_update($mem_id, $chat_room_id, $status)
    {
        $db = db_connect();
        $data = $db->table('product_offer po')
            ->select('po.dealer_id, p.mem_idx')
            ->join('product p', 'po.product_id = p.product_id')
            ->where('offer_hash_id', $chat_room_id)
            ->get()->getRowArray();
        $who_is = false;

        if ($data) {
            if ($data['dealer_id'] ==  $mem_id)  $who_is = 'dealer_id';
            if ($data['mem_idx'] ==  $mem_id)  $who_is = 'onner_id';
        } else {
            return ['code' => 442, 'message' => 'not match room', 'data' => []];
        }

        $builder = $db->table('product_offer');
        $code = 441;
        $message = 'not match member';
        $result = null;
        if ($who_is == 'dealer_id') {
            //dealer
            $builder->set('bell_status_dealer', $status);
            $builder->where('offer_hash_id', $chat_room_id);
            $result = $builder->update();
            $code = 200;
            $message = "bell_status_dealer update";
        } else if ($who_is == 'onner_id') {
            //onner 
            $builder->set('bell_status_onner', $status);
            $builder->where('offer_hash_id', $chat_room_id);
            $result = $builder->update();
            $code = 200;
            $message = "bell_status_onner update";
        } else {
            // not 
        }

        return ['code' => $code, 'message' => $message, 'data' => $result];
    }

    public function review_info(int $product_id)
    {
        $db = db_connect();
        $builder = $db->table('reviews');
        $builder
            ->join('product', 'reviews.product_id = product.product_id', 'inner')
            ->join('product_thumbnail', 'product_thumbnail.product_id = product.product_id', 'inner')
            ->select('reviews.reviews_id, reviews.product_id, reviews.star_point, reviews.good_choice, reviews.bad_choice, reviews.created_at ,
            product.price, product.offer_price, product.product_name, product_thumbnail.img_url, reviews.content')
            ->where('reviews.product_id', $product_id)
            ->where('product_thumbnail.is_first', 1);
        $data = $builder->get()->getRowArray();
        return ['code' => 200, 'message' => 'review_info', 'data' => $data];
    }

    public function review_info_id(int $reviews_id)
    {
        $db = db_connect();
        $builder = $db->table('reviews');
        $builder
            ->join('product', 'reviews.product_id = product.product_id', 'inner')
            ->join('product_thumbnail', 'product_thumbnail.product_id = product.product_id', 'inner')
            ->select('reviews.reviews_id, reviews.product_id, reviews.star_point, reviews.good_choice, reviews.bad_choice, reviews.created_at
                    ,product.price, product.offer_price, product.product_name, product_thumbnail.img_url, reviews.onner_hash_id
                    , reviews.type , reviews.content
            ', false)
            ->where('reviews.reviews_id', $reviews_id)
            ->where('product_thumbnail.is_first', 1);
        $data = $builder->get()->getRowArray();
        return ['code' => 200, 'message' => 'review_info', 'data' => $data];
    }

    /**
     * 중복 오퍼 인지 체크
     */
    public function offer_duplicate_check($product_id, $dealer_id)
    {
        $db = db_connect();
        $builder = $db->table('product_offer po');
        $builder
            ->where('po.dealer_id', $dealer_id)
            ->where('po.product_id', $product_id)
            ->where('po.chat_status_dealer !=', 4); //구매자가 나간 경우만 다시 요청가능
        $data = $builder->get()->getRowArray();
        log_message('info', $this->db->lastQuery);
        return ['code' => 200, 'message' => 'offer_duplicate_check', 'data' => $data];
    }

    public function offer_duplicate_price_update($product_id, $dealer_id, $offer_price)
    {
        $db = db_connect();
        $builder = $db->table('product_offer po');
        $builder
            ->set('offer_price', $offer_price)
            ->where('po.dealer_id', $dealer_id)
            ->where('po.product_id', $product_id)
            ->update();
        return ['code' => 200, 'message' => 'offer price update', 'data' => []];
    }

    public function onner_offer_update($product_id, $dealer_id)
    {
        $db = db_connect();
        $builder = $db->table('product_offer po');
        $builder
            ->set('po.chat_status', 1)
            ->where('po.dealer_id', $dealer_id)
            ->where('po.product_id', $product_id)
            ->update();
        return ['code' => 200, 'message' => 'offer price update', 'data' => []];
    }

    /**
     * 블록유저 체크
     * 577 밴아이디
     * 477 상품데이터 잘못됨
     */

    public function check_block_user($product_id, $dealer_id)
    {
        $db = db_connect();
        $builder = $db->table('product');
        $onner_id = $builder->select('mem_idx')
            ->where('product_id', $product_id)
            ->get()
            ->getRowObject();

        if ($onner_id) {
            $builder = $db->table('member_user_ban');
            $ban_id = $builder
                ->select('ban_id')
                ->where('member_id', $onner_id->mem_idx)
                ->where('ban_id', $dealer_id)
                ->where('status', 1)
                ->get()
                ->getResult();
            if ($ban_id) {
                return ['code' => 577]; //밴 아이디
            } else {
                return ['code' => 200]; //이상 없음
            }
        } else {
            return ['code' => 477]; //상품데이터 잘못됨
        }
    }

    /**
     * 상대방만 나갔을 경우 다시 초대하는 것으로 업데이트 
     * 상태는 0 초기 상태
     * 폐기
     */
    public function offer_chat_update($offer_hash_id)
    {
        $db = db_connect();
        $builder = $db->table('product_offer')->where('offer_hash_id', $offer_hash_id);
        $data = $builder->get()->getRowArray();

        if ($data) {
            $builder = $db->table('product_offer');
            if ($data['chat_status'] == 4 && $data['chat_status_dealer'] <= 2) {
                $builder->set('chat_status', 0);
            } else if ($data['chat_status'] <= 2 && $data['chat_status_dealer'] == 4) {
                $builder->set('chat_status_dealer', 0);
            }
            $builder->where('offer_hash_id', $offer_hash_id);
            $builder->update();
        }
    }

    public function product_shares(int $product_id)
    {
        $db = db_connect();
        $db->table('product')->set('shares', 'shares+1', false)->where('product_id', $product_id)->update();
        return ['code' => 200, 'message' => 'product_shares up', 'data' => []];
    }

    public function product_image_check(int $product_id)
    {
        $db = db_connect();
        $data = $db->table('product_thumbnail')->selectCount('*', 'cnt')->where('product_id', $product_id)->get()->getRowArray();
        return ['code' => 200, 'message' => 'product_shares up', 'data' => [$data['cnt']]];
    }

    public function product_upstream(int $product_id, int $mem_id)
    {
        $db = db_connect();
        $builder = $db->table('product')
            ->where('product.product_id', $product_id)
            ->where('product.mem_idx', $mem_id)
            ->where('product.upstream_at >=', 'DATE_ADD(NOW(), INTERVAL -3 DAY)', false);
        $row = $builder->get()->getRowArray();

        if ($row) {
            $builder = $db->table('product')
                ->where('product.product_id', $product_id)
                ->where('product.mem_idx', $mem_id)
                ->where('product.upstream_at =', 'product.created_at', false); //만든시간과 동일 하면
            $row2 = $builder->get()->getRowArray();
            log_message('info', $this->db->lastQuery);
            if ($row2) {
                $row = null;
            }
        }

        if ($row) {
            return ['code' => 333, 'message' => "Each posting can only bump up every 72 hours from last bump up.", 'data' => []];
        }

        $db->table('product')
            ->set('updated_at', 'now()', false)
            ->set('upstream_at', 'now()', false)
            ->where('product_id', $product_id)->update();
        return ['code' => 200, 'message' => 'You have successfully bumped up your posting!', 'data' => []];
    }


    public function product_recommend()
    {
        $db = db_connect();
        return [
            'code' => 200, 'message' => 'recommend',
            'data' => $db->table('product_recommend')->orderBy('weight', 'desc')->orderBy('prc_idx', 'desc')->get()->getResultArray()
        ];
    }

    public function product_preference($data)
    {
        $db = db_connect();
        return [
            'code' => 200, 'message' => 'product_preference',
            'data' => $db->table('product_preference')->insert($data)
        ];
    }

    public function product_preference_modify($data)
    {
        $db = db_connect();
        $db->table('product_preference')->where('product_id', $data['product_id'])->delete();
        return [
            'code' => 200, 'message' => 'product_preference_modify',
            'data' => $db->table('product_preference')->insert($data)
        ];
    }

    /**
     * 
     */
    public function offer_ban_update($dealer_id, $product_id, $offer_hash_id)
    {
        $db = db_connect();
        $member = $db->table('product')->select('mem_idx')->where('product_id', $product_id)->get()->getRowArray();
        //log_message(5, $this->db->lastQuery);
        $member_id = $member['mem_idx'] ?? 0;
        $data = [];

        if ($member_id) {
            $data = $db->table('member_user_ban')
                ->where('member_id', $member_id)
                ->where('ban_id', $dealer_id)
                ->get()->getRowArray();
            log_message(5, $this->db->lastQuery);
        }

        //ban data 
        if ($data) {
            //offer_ban_update('390', 66, '639c415b1f7c9')
            //판매자는 자동으로 방 나감 상태로 생성되서 처리됨
            $this->db->table('product_offer')->set('chat_status', 4)->where('offer_hash_id', $offer_hash_id)->update();
            log_message(5, $this->db->lastQuery);
        }
    }


    public function cron_fcm_chat_reserv_reg($room_id, $meet_time)
    {
        $db = db_connect();
        $offer_data = $db->table("product_offer")->where('offer_hash_id', $room_id)->limit(1)->get()->getRowArray();

        if (!isset($offer_data["product_id"])) {
            return ['code' => 404, 'message' => 'not data', 'data' => []];
        }

        $onner_id = $db->table("product")->select('mem_idx')->where('product_id', $offer_data["product_id"])->limit(1)->get()->getRowArray();
        $data['dealer_data'] = $db->table("member_push_toekn")->select('member_id, device_id,type')->where('member_id', $offer_data["dealer_id"])->limit(1)->get()->getRowArray();
        $data['onner_data'] = $db->table("member_push_toekn")->select('member_id, device_id,type')->where('member_id', $onner_id)->limit(1)->get()->getRowArray();


        //print_r($data);
        $insert_data = [];
        if (isset($data['dealer_data']['device_id'])) {
            $insert_data['dealer_push_token'] = $data['dealer_data']['device_id'];
            $insert_data['dealer_token_type'] = $data['dealer_data']['type'];
        }

        if (isset($data['dealer_data']['device_id'])) {
            $insert_data['onner_push_token'] = $data['onner_data']['device_id'];
            $insert_data['onner_token_type'] = $data['onner_data']['type'];
        }

        $insert_data['meet_time'] = $meet_time;
        $timestamp = strtotime("-5 minutes", strtotime($meet_time));
        $insert_data['push_alert_time'] = date("Y-m-d H:i:s", $timestamp);
        $insert_data['push_call_check'] = 0;

        // print_r($insert_data);
        // exit;

        $status = $db->table("chat_push_plan_cron")->insert($insert_data);
        if ($status) {
            return ['code' => 200];
        } else {
            return ['code' => 501, 'message' => 'insert err'];
        }
    }

    public function fcm_chat_reserv_send_list()
    {
        $db = db_connect();
        return $db->table("chat_push_plan_cron")->where('push_alert_time <=', 'now()', false)->where('push_call_check', 0)->get()->getResultArray();
    }

    public function fcm_chat_reserv_update($cppc_idx_array)
    {
        $db = db_connect();
        $db->table("chat_push_plan_cron")->set('push_call_check', 1)->whereIn('cppc_idx', $cppc_idx_array)->update();
    }
}
