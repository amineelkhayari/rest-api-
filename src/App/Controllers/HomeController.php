<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;

class HomeController
{
    #[\App\Route(path: '/health', method: 'GET')]
    public function health(Request $req, Response $res)
    {
        return $res->json([
            'status' => $req,
            'time' => date('c'),
            
        ]);
    }

    #[\App\Route(path: '/test', method: 'GET')]
    public function test(Request $req, Response $res)
    {
        return $res->json([
            'status' => 'ok',
            'data'=>'re'
        ]);
    }

    #[\App\Route(path: '/local', method: 'GET')]
    public function local(Request $req, Response $res)
    {
        return $res->json([
            'status' => 'local',
            'data'=>'min'
        ]);
    }
}