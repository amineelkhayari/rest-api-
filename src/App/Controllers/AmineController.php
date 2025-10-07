<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;
use \App\Helpers\Route;
use \App\Helpers\Authorize;
use \App\Helpers\AllowAnonymous;
use \App\Helpers\ApiController;

#[ApiController]
class AmineController
{
    // In-memory store just for demo
    private array $users = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];

    #[Route(path: '/amine', method: 'GET')]
    public function index(Request $req, Response $res)
    {
        $dt = $req->user;
        return $res->json(['data' =>$this->users]);
    }

    

   
}