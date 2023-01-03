<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class BadgeModel extends Model
{
    protected $table = 'member';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';
    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    public function green_info($member_hash_id)
    {

        $db = db_connect();
        $builder = $db->table('member m');
        $builder->select('m.member_id, m.hash_id, m.sns_type, m.name, m.status, mb.get_point, m.picture')
            ->join('member_badges mb', 'm.member_id = mb.member_id')
            ->where('m.hash_id', $member_hash_id);
        $data = $builder->get()->getRowArray();

        if ($data) {
            return ['code' => 200, 'message' => 'get green info', 'data' => $data];
        } else {
            return ['code' => 404, 'message' => 'not match data', 'data' => []];
        }
    }

    public function review_count($member_hash_id)
    {

        $db = db_connect();
        $member_id = $db->table('member')->select('member_id')->where('hash_id', $member_hash_id)->limit(1)->get()->getRowArray();

        $builder = $db->table('reviews');
        $builder->select('star_point, good_choice, bad_choice, content ')
            ->where('target_id', $member_id);
        $data = $builder->get()->getResultArray();

        if ($data) {
            return ['code' => 200, 'message' => 'get green info', 'data' => $data];
        } else {
            return ['code' => 404, 'message' => 'not match data', 'data' => []];
        }
    }

    public function green_info_content_list($input)
    {
        $member_hash_id = $input['hash_id'] ?? "";
        $page = $input['page'] ?? 0;

        $db = db_connect();
        $member_id = $db->table('member')->select('member_id')->where('hash_id', $member_hash_id)->limit(1)->get()->getRowArray();

        $builder = $db->table('reviews');
        $builder->select('reviews.content, (select name from member where  member_id = reviews.reviewer_id  ) revierwe_name  ', false)
            ->where('reviews.target_id', $member_id);

        $per_page = 30; //to 50
        if (is_numeric($page) == false) $page = 1;
        if ($page <= 0) $page = 1;

        $offset = ($page - 1) * $per_page;
        $builder->orderBy("reviews.reviews_id", "DESC");
        $builder->limit($per_page, $offset);
        $data = $builder->get()->getResultArray();

        if ($data) {
            foreach ($data as $key => $val) {
                if ($key == 'revierwe_name') $data[$key]['revierwe_name'] = preg_replace('/(?<=.{2})./u', '*', $val['revierwe_name']);
            }
            return ['code' => 200, 'message' => 'get green green_info_content_list', 'data' => $data];
        } else {
            return ['code' => 404, 'message' => 'not match data', 'data' => []];
        }
    }
}
