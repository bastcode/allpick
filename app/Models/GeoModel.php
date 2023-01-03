<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class GeoModel extends Model
{
    protected $table = 'geo';
    protected $allowedFields = [
        'latitude', 'longitude', 'station'
    ];
    protected $updatedField = 'updated_at';
    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array
    {
        return $data;
    }

    /**
     * 
     * 100m 이내 현재 위치에서 가까운 순서
     * SELECT * FROM goe WHERE
     * ST_DISTANCE(GEOMFROMTEXT( 'POINT(126.905 37.5158)', 4326 ), GEOMFROMTEXT( CONCAT('POINT(', longitude, ' ', latitude, ')'), 4326 )) * 111195 < 100 
     * ORDER BY ST_DISTANCE(GEOMFROMTEXT( 'POINT(126.905 37.5158)', 4326 ), GEOMFROMTEXT( CONCAT('POINT(', longitude, ' ', latitude, ')'), 4326 )) ASC LIMIT 10

     * SELECT * FROM aera_geo  WHERE
     * ST_DISTANCE(GEOMFROMTEXT( 'POINT(1.4235346 103.8033499)', 4326 ), 
     * GEOMFROMTEXT( CONCAT('POINT(', latitude, ' ', longitude , ')'), 4326 )) * 111195 < 100 
     * ORDER BY ST_DISTANCE(GEOMFROMTEXT( 'POINT(1.4235346 103.8033499)', 4326 ) , GEOMFROMTEXT( CONCAT('POINT(', latitude, ' ', longitude, ')'), 4326 )) 
     * ASC LIMIT 10
     */
}
