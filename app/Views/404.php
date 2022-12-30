<?php
header('Content-Type: application/json; charset=utf-8');
$data = ['code'=>404, 'message'=>'not url match'];
echo( json_encode($data));
//http_response_code(404);
