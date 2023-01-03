<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class CustomerModel extends Model
{
    protected $table = 'board';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';
    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array
    {
        return $data;
    }

    public  function notice_list()
    {
        $db = db_connect();
        $builder = $db->table('board')->where('type', 1)->orderBy('board_id', 'desc'); //1 notice , 2 event, 3 pop, 4 etc 
        $data = $builder->get()->getResultArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    public function notice_info($board_id)
    {
        $db = db_connect();
        $builder = $db->table('board')->where('board_id', $board_id);
        $data = $builder->get()->getRowArray();
        return ["code" => 200, "message" => "list ok", "data" => $data];
    }

    public function notice_info_check($board_id, $member_id)
    {
        $db = db_connect();
        $builder = $db->table('board_noti')->where('member_id', $member_id)->where('board_id', $board_id);
        $data = $builder->get()->getRowArray();

        if (!$data) {
            $builder->$db->table('board_noti');
            $builder->insert([
                'board_id' => $board_id,
                'member_id' => $member_id,
            ]);
            return ["code" => 200, "message" => "check insert ok", "data" => []];
        }
        return ["code" => 200, "message" => "check update ok", "data" => []];
    }

    public function report_proc(array $data, array $thumbnail_data)
    {
        $db = db_connect();
        $builder = $db->table('report');
        $builder->insert($data);

        $report_id = $db->insertID();
        //$i = 0;
        //$s3_path = getenv('S3_PATH');
        foreach ($thumbnail_data as $key => $val) {
            $img =  $val['ObjectURL'];
            if ($key == 0) {
                $builder->set('uploads1', $img);
            } else {
                $builder->set('uploads2', $img);
            }
            $builder->where('report_id', $report_id);
            $builder->update();
        }
    }

    public function event_popup_list($type, $sdate, $edate)
    {
        $db = db_connect();
        $builder = $db->table("board b")
            ->select('b.*, bt.img_url');
        $builder->join('board_thumbnail bt', 'b.board_id = bt.board_id', 'left');

        $builder->where("b.status !=", 4); //삭제 제외

        if ($type == 'notice') {
            $builder->where("type", 1);
        } else if ($type == 'event') {
            $builder->where("type", 2);
        } else if ($type == 'popup') {
            $builder->where("type", 3);
        }


        $builder->where("b.notice_start_at <=", 'now()', false);
        $builder->where("b.notice_end_at >", 'now()', false);
        $builder->where("b.is_show =", 1);

        $data = $builder->get()->getResultArray();

        // $data = $builder->get()->getRowArray();
        // if($data) {
        //     foreach($data as $key=>$val) {
        //         //echo $key; exit;
        //         if($key == 'notice_start_at') $data['notice_start_at'] = date("Y-m-d", strtotime($val));
        //         if($key == 'notice_end_at') $data['notice_end_at'] = date("Y-m-d", strtotime($val));
        //     }
        // }
        //echo $this->db->lastQuery;

        if ($data) {
            foreach ($data as $key => $val) {
                $data[$key]['notice_start_at'] =    date("Y-m-d", strtotime($val['notice_start_at']));
                $data[$key]['notice_end_at'] =      date("Y-m-d", strtotime($val['notice_end_at']));
            }
        }


        // $builder->join('board_thumbnail bt', 'b.board_id = bt.board_id', 'left');
        // $builder->select('bt.img_url, bt.board_id');
        // $builder->where("b.status !=", 4); //삭제 제외
        // if($type == 'notice') {
        //     $builder->where("type", 1);
        // } else if($type == 'event') {
        //     $builder->where("type", 2);
        // } else if($type == 'popup') {
        //     $builder->where("type", 3);
        // }
        // $builder->where("b.notice_start_at <=", 'now()', false);
        // $builder->where("b.notice_end_at >", 'now()', false);
        // $builder->where("b.is_show =", 1);
        // $data['board_thumbnail'] = $builder->get()->getResultArray();


        return  $data;
    }
}
