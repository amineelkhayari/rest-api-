<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;
use \App\Helpers\Route;

class UserController
{
    // In-memory store just for demo
    private array $users = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];

    #[Route(path: '/v1/users', method: 'GET')]
    public function index(Request $req, Response $res)
    {
        $dt = $req->user;
        return $res->json(['data' =>$dt]);
    }

    #[Route(path: '/v1/users/{id}', method: 'GET')]
    public function show(Request $req, Response $res, array $params)
    {
        $id = (int)($params['id'] ?? 0);
        foreach ($this->users as $u) if ($u['id'] === $id) return $res->json($u);
        return $res->json(['error' => 'User not found'], 404);
    }

    #[Route(path: '/v1/users', method: 'POST')]
    public function store(Request $req, Response $res)
    {
        $body = $req->getBody();
        if (!isset($body['name']) || $body['name'] === '') {
            return $res->json(['error' => 'Name is required'], 422);
        }
        $new = ['id' => rand(3, 10000), 'name' => $body['name']];
        return $res->json($new, 201);
    }
}