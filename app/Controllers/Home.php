<?php

namespace App\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use CodeIgniter\Controller;

class Home extends BaseController
{

    public function __construct()
    {
        //helper('jwt');
    }

    public function index()
    {
        //$this->response->redirect('/front');
        //echo view('welcome_message');
        echo 'is only api end point';
    }

    public function main()
    {

        $view = \Config\Services::renderer();

        //$this->init_jwt_check();

        echo $view->render('header/head');
        echo $view->render('main');
        echo $view->render('header/foot');
    }

    public function login()
    {
        // $view = \Config\Services::renderer();
        // echo $view->render('header/head');
        // echo $view->render('sns_login');
        // echo $view->render('header/foot');
    }

    public function token()
    {
        $request = \Config\Services::request();
        $header = $request->getHeader("Authorization");
        $token = null;

        // extract the token from the header
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                echo $token = $matches[1];
            }
        }

        var_dump($header);
    }

    public function cros2()
    {

        $view = \Config\Services::renderer();
        echo $view->render('header/head');
        echo $view->render('cros');
        echo $view->render('header/foot');
    }
}
