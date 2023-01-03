<?php

namespace App\Models;

use CodeIgniter\Model;

class AppModel extends Model
{
    protected $table = 'member';
    protected $allowedFields = [];
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    public function versions($version, $type)
    {

        if ($type == 'apple') {
            $app_type = 1;
        } else {
            $app_type = 2;
        }


        $db = db_connect();
        $query = "SELECT t1.app_version,  t1.app_forced_push, t1.num,
                INET_ATON(
                    CONCAT(
                        app_version,
                        REPEAT('.0', 3 - CHAR_LENGTH(app_version) + CHAR_LENGTH(REPLACE(app_version, '.', '')))
                    )
                ) num_v
                FROM
                (SELECT
                    app_version,
                    app_forced_push,
                    app_type,
                    INET_ATON(
                        CONCAT(
                            ?, 
                            REPEAT('.0',3 - CHAR_LENGTH(?) + CHAR_LENGTH(REPLACE(?, '.', '')))
                        )
                ) num
            FROM
                `app_version`) t1
            WHERE app_type = $app_type
            HAVING num_v > num
            ORDER BY num_v DESC
        ";
        $rows = $db->query($query, [$version, $version, $version])->getResultArray();
        //log_message('info' , $this->db->lastQuery);

        $first_array = [];
        $app_forced_push = 1; //최신이고 강업이 없으면 0 약업이면 1
        //$app_forced_push_chk = 0;
        foreach ($rows as $key => $val) {
            if ($key == 0) $first_array = $val;
            if ($val['app_forced_push']  == 1) {
                //강업이 1개라도 있으면 강제업데이트 2
                $app_forced_push = 2;
            }
        }

        // if($app_forced_push_chk == 2) {
        //     //강업 체크가 걸리면 2
        //     $app_forced_push = 2;
        // }

        // if(count($first_array) >= 1 ) {
        //     //업데이트 할게 있으면 약업
        //     $app_forced_push = 1;
        // }

        $first_array['app_forced_push'] = $app_forced_push;
        $first_array['app_type'] = $type;

        if (count($rows) == 0) {
            //최신버전인경우
            $query = "SELECT
                app_type, app_version, app_forced_push,
              INET_ATON(
                CONCAT(
                  app_version,
                  REPEAT(
                    '.0',
                    3 - CHAR_LENGTH(app_version) + CHAR_LENGTH(REPLACE(app_version, '.', ''))
                  )
                )
              ) num_v
            FROM
              app_version
            WHERE app_type = $app_type
            ORDER BY num_v DESC
            LIMIT 1";
            $first_array = $db->query($query)->getRowArray();
            $first_array['app_forced_push'] = 0; //고정 업데이트 버전이 없으니깐
            $first_array['app_type'] = $type;
        }

        return ["code" => 200, "data" => $first_array];
    }
}
