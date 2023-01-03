<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class CategoryModel extends Model
{
    protected $table = 'category_sub';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';
    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array
    {
        return $data;
    }

    /**
     * 상품 카운트를 포함한 전체 카테고리 정보
     */
    public function GetCatetory()
    {
        $db = db_connect();
        //,(SELECT COUNT(*) FROM product WHERE product.category_code_id = cc.`category_code_id` ) cnt //상품숫자는 제외
        $sql = 'SELECT c.`category_name`, c.`origin_code`, cc.`category_sub_code`, cc.`category_sub_name`, cc.`category_code_id`,  0 as cnt
                FROM category c 
                LEFT JOIN category_code cc ON  c.`category_id` = cc.`category_id` 
                WHERE cc.category_code_hide = 1
                ORDER BY c.`category_name` , cc.`category_sub_name`
        ';
        $data = $this->db->query($sql, null, false)->getResultArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    /**
     * 상품 카운트를 제외한 단순 카테고리 리스트
     */
    public function GetSimpleCatetory()
    {
        $db = db_connect();

        $sql = 'SELECT c.`category_name`, c.`origin_code`, cc.`category_sub_code`, cc.`category_sub_name`, cc.`category_code_id`                
            FROM category c 
            LEFT JOIN category_code cc ON  c.`category_id` = cc.`category_id` 
            WHERE cc.category_code_hide = 1 
            ORDER BY c.`category_name` , cc.`category_sub_name`
        ';
        $data = $this->db->query($sql, null, false)->getResultArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    public function GetCatetoryInfo(int $cate_code)
    {
        $db = db_connect();

        $sql = 'SELECT c.`category_name`, c.`origin_code`, cc.`category_sub_code`, cc.`category_sub_name`, cc.`category_code_id`                
            FROM category c 
            LEFT JOIN category_code cc ON  c.`category_id` = cc.`category_id` 
            WHERE cc.`category_code_id` = ?
        ';
        $data = $this->db->query($sql, [$cate_code])->getResultArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }
}
