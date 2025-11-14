<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;
use \App\Helpers\Attributes\AllowAnonymous;
use \App\Helpers\Attributes\ApiController;
use \App\Helpers\Attributes\Authorize;
use \App\Helpers\Attributes\Route;

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
        json_encode($req);
        return $res->json(['data' => $this->users]);
    }
}
