<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class AccountModel extends Model
{
    protected $table = 'member';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    /**
     * 카테고리 GEO 
     * aera id 없으면 1단 카테고리
     * aera id 넣으면 해당 지역의 2단 카테고리
     */
    public function GetGeoCatetory(int $step, int $aera_id = 0)
    {
        $db = db_connect();
        if ($aera_id == 0) {
            $builder = $db->table("aera");
            return $builder->get()->getResultArray();
        } else {
            $builder = $db->table("aera_geo");
            $builder->where('aera_id', $aera_id);
            return $builder->get()->getResultArray();
        }
    }

    /**
     * 뱃지 포함 회원 정보
     */
    public function GetMyaccountInfo(int $mem_id = 1)
    {
        //,`mg`.`selected`
        $db = db_connect();
        $sql = 'SELECT `hash_id`, `sns_type`,`name`, `email`, `picture`,`mb`.`get_point`
        -- `member_geo_id`,`latitude`,`longitude`,`station1`,`station2`,        
        -- `bd`.`badges_image`, `bd`.`badges_title`
        FROM `member` m 
        -- LEFT JOIN `member_geo` mg ON  m.member_id = mg.member_id
        LEFT JOIN `member_badges` mb ON m.member_id = mb.member_id
        -- LEFT JOIN `badges` bd ON bd.badges_id = mb.badges_id
        WHERE m.member_id = ?';
        $rows = $db->query($sql, [$mem_id])->getResultArray();

        $data = ["data" => $rows, "code" => 200, "message" => "get list ok"];
        return $data;
    }

    /**
     * 나의 지역 등록
     */
    public function putMemberGeoSave(int $mem_id, string $cate_local_1, string $cate_local_2, float $latitude, float $longitude)
    {

        $aera_step_1 = $cate_local_1; //받아온값 그대로 우선 셋팅

        $aera_data = $this->db->table('aera_geo ag')
            ->select('ag.aera_step2, a.aera_step1')
            ->join('aera a', 'ag.aera_id = a.aera_id ', 'inner')
            ->whereIn('aera_step2', [$cate_local_1, $cate_local_2])->get()->getRowArray();

        if (!$aera_data) {

            $this->db->table('aera_geo')->insert([
                'aera_id' => 7,
                'aera_step2' => $cate_local_1,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            $this->db->table('aera_geo')->insert([
                'aera_id' => 7,
                'aera_step2' => $cate_local_2,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
        } else {
            if ($aera_data['aera_step1'] != 'Custom') $aera_step_1 = $aera_data['aera_step1']; //등록된 데이터가 있으면 
        }

        $member_geo = $this->db->table('member_geo')->where(['member_id' => $mem_id])->get()->getResultArray();
        $member_geo_count = 0;
        $member_geo_select_idx = 0;
        $member_geo_select_etc_idx = 0;
        $member_geo_select_last_idx = 0; //마지막 인덱스


        foreach ($member_geo as $key => $val) {
            $member_geo_count++;
            if ($latitude == $val['latitude'] && $longitude == $val['longitude']) {
                //같은 지역 업데이트
                $member_geo_select_idx = $val['member_geo_id'];
            } else {
                //같지 않은 지역
                $member_geo_select_etc_idx = $val['member_geo_id'];
            }
            $member_geo_select_last_idx = $val['member_geo_id'];
        }

        $message = "";
        if (!$member_geo_count || $member_geo_count == 0) {
            //아예 없으면 
            $this->db->table('member_geo')->insert([
                'member_id' => $mem_id,
                'station1' => $aera_step_1,
                'station2' => $cate_local_2,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'selected' => 1
            ]);
            $message = "insert!";
        } else if ($member_geo_count == 1) {
            if ($member_geo_select_idx > 0) {
                $this->db->table('member_geo')
                    ->where('member_id', $mem_id)
                    ->where('member_geo_id', $member_geo_select_idx)
                    ->update([
                        'station1' => $aera_step_1,
                        'station2' => $cate_local_2,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'selected' => 1
                    ]);
            } else if ($member_geo_select_etc_idx > 0) {
                //1개 등록되었는데 등록되지 않은 다른 지역이면 기존 1개를 비선택으로 업데이트 선택만 초기화
                $this->db->table('member_geo')
                    ->where('member_id', $mem_id)
                    ->update([
                        'selected' => 0
                    ]);
                //새로 등록
                $this->db->table('member_geo')->insert([
                    'member_id' => $mem_id,
                    'station1' => $aera_step_1,
                    'station2' => $cate_local_2,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'selected' => 1
                ]);
            }
        } else if ($member_geo_count >= 2) {
            //2개 이상에서 변경시 전체 사용안함으로 업데이트
            $this->db->table('member_geo')
                ->where('member_id', $mem_id)
                ->update([
                    'selected' => 0
                ]);

            //마지막 row를 사용으로 업데이트
            $this->db->table('member_geo')
                ->where('member_id', $mem_id)
                ->where('member_geo_id', $member_geo_select_last_idx)
                ->update([
                    'station1' => $aera_step_1,
                    'station2' => $cate_local_2,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'selected' => 1
                ]);

            $message = "update!";
        }

        return [200, $message];
    }

    /**
     * 나의 지역 등록 멀티 버전
     * v2
     */
    public function putMemberGeoSaveMulti(int $mem_id, array $data)
    {
        $message = "";
        $insertID = 0;
        foreach ($data as $key => $val) {

            if ($key == 0) {
                $this->db->table('member_geo')->where('member_id', $mem_id)->delete(); //처음만 우선 지역정보 다 날림
            }

            $cate_local_1 = $val['station1'] ?? '';
            $cate_local_2 = $val['station2'] ?? '';
            $aera_step_1 = $cate_local_1;

            $latitude = $val['latitude'] ?? 1.292861;
            $longitude = $val['longitude'] ?? 103.852689;

            $aera_data = $this->db->table('aera_geo ag')
                ->select('ag.aera_step2, a.aera_step1')
                ->join('aera a', 'ag.aera_id = a.aera_id ', 'inner')
                ->whereIn('aera_step2', [$cate_local_1, $cate_local_2])->get()->getRowArray();

            if (!$aera_data) {
                $this->db->table('aera_geo')->insert([
                    'aera_id' => 7,
                    'aera_step2' => $cate_local_1,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
                $this->db->table('aera_geo')->insert([
                    'aera_id' => 7,
                    'aera_step2' => $cate_local_2,
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
            } else {
                if ($aera_data['aera_step1'] != 'Custom') $aera_step_1 = $aera_data['aera_step1']; //등록된 데이터가 있으면 
            }


            $this->db->table('member_geo')->insert([
                'member_id' => $mem_id,
                'station1' => $aera_step_1,
                'station2' => $cate_local_2,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'selected' => 0
            ]);
            $message = "insert!";
            $insertID = $this->db->insertID();
        }
        // 마지막 in
        if ($insertID > 0) {
            $this->db->table('member_geo')->set('selected', 1)->where('member_geo_id', $insertID)->update();
        }
        return [200, $message];
    }

    public function putMemberGeoSelected(int $mem_id, int $member_geo_id)
    {

        $this->db->transStart();
        //기본 데이터 비선택으로 초기화
        $this->db->table('member_geo')
            ->set('selected', 0)
            ->where('member_id', $mem_id)
            ->update();

        $this->db->table('member_geo')
            ->set('selected', 1)
            ->where('member_id', $mem_id)
            ->where('member_geo_id', $member_geo_id)
            ->update();
        $result = $this->db->transComplete();
        return $result;
    }

    public function member_push_toekn_list()
    {
        $db = db_connect();
        $sql = 'SELECT mpt.`device_id` , mpt.type
        FROM `member` m
        INNER JOIN `member_push_toekn` mpt on m.member_id = mpt.member_id
        INNER JOIN `member_alarm` ma on m.member_id = ma.member_id
        WHERE m.status != 4 AND ma.announcements = 1';
        $rows = $db->query($sql)->getResultArray();
        return ["data" => $rows, "code" => 200, "message" => "get  push list"];
    }
}
