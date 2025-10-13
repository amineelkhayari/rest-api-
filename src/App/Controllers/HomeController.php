<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use \App\Helpers\Route;
use \App\Helpers\Authorize;
use \App\Helpers\AllowAnonymous;
use \App\Helpers\ApiController;
use Core\Database;

//#[Route("/api")]
#[ApiController]
//#[AllowAnonymous]
#[Authorize([], "accident-api")]
class HomeController
{

    private Database $db;
        private EntityManagerInterface $em;

    // public function __construct(?Database $db)
    // {
    //     $this->db = $db ?? new Database() ;
    // }

    //  public function __construct(?EntityManagerInterface $em = null)
    // {
    //     // fallback: use global or static instance
    //     $this->em = $em ?? $GLOBALS['entityManager'];
    // }

    #[Route(path: '/health', method: 'GET')]
    #[Authorize(['accident.basique', "accident.admin", "accident.superadmin"])]
    public function health(Request $req, Response $res)
    {
        return $res->json([
            'status' => $req,
            'time' => date('c'),

        ]);
    }

    #[Route(path: '/test', method: 'GET')]
    #[AllowAnonymous]
    public function test(Request $req, Response $res)
    {
        return $res->json([
            'status' => 'ok',
            'data' => 're',
            "er"=>$this->db->getConnection()
        ]);
    }

    #[Route(path: '/local', method: 'GET')]
    public function local(Request $req, Response $res)
    {
        return $res->json([
            'status' => 'local',
            'data' => 'min'
        ]);
    }
}
