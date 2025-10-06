<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;
use \App\Helpers\Route;


//#[Route("/api")]
#[\App\Helpers\ApiController]
class HomeController
{
    #[Route(path: '/health', method: 'GET')]
    public function health(Request $req, Response $res)
    {
        return $res->json([
            'status' => $req,
            'time' => date('c'),
            
        ]);
    }

    #[Route(path: '/test', method: 'GET')]
    public function test(Request $req, Response $res)
    {
        return $res->json([
            'status' => 'ok',
            'data'=>'re'
        ]);
    }

    #[Route(path: '/local', method: 'GET')]
    public function local(Request $req, Response $res)
    {
        return $res->json([
            'status' => 'local',
            'data'=>'min'
        ]);
    }
}