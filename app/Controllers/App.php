<?php

namespace App\Controllers;

use CodeIgniter\Controller;

use App\Models\AppModel;

class App extends BaseController
{

    public function __construct()
    {
    }

    public function versions()
    {

        $input = $this->getRequestInputGetType($this->request);
        $app_type = $input['app_type'] ?? 'apple';
        $version =  $input['version'] ?? '';

        $AppModel = new AppModel();
        $data = $AppModel->versions($version, $app_type);
        $this->getResponseSend(["code" => $data['code'], "message" => "this is version api", "data" => $data['data']], 200);
    }
}
