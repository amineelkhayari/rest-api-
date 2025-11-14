<?php
namespace App\Controllers;

use Core\Entities\Post;  // ✅ Add this line
use Core\Entities\User;
use Core\Database;
use Core\Request;
use Core\Response;
use \App\Helpers\Attributes\AllowAnonymous;
use \App\Helpers\Attributes\ApiController;
use \App\Helpers\Attributes\Authorize;
use \App\Helpers\Attributes\Route;

#[ApiController]
// #[\App\Helpers\Authorize]
class UserController
{
    private Database $db;

    public function __construct(?Database $db)
    {
        $this->db = $db ?? new Database();
    }

    // In-memory store just for demo
    private array $users = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];

    #[Route(path: '/v1/users', method: 'GET')]
   // #[Authorize(['lll'])]
    public function index(Request $req, Response $res)
    {
                json_encode($req);

        $users = $this->db->getData(User::class);

        $userData = array_map(function ($u) {
    $posts = array_map(fn($p) => [
        'title' => $p->getTitle(),
        'content' => $p->getContent(),
    ], $u->getPosts()->toArray());

    return [
        'email' => $u->getEmail(),
        'posts' => $posts,
    ];
}, $users);
        return $res->json(['data' => $userData]);
    }

    #[Route(path: '/v1/users/{id}', method: 'GET')]
    // #[Authorize(['basique'])]
    public function show(Request $req, Response $res, array $params)
    {
                json_encode($req);

        $id = (int) ($params['id'] ?? 0);
        foreach ($this->users as $u)
        {
         if ($u['id'] === $id){return $res->json($u);}
        }
    return $res->json(['error' => 'User not found'], 404);
    }

    #[Route(path: '/v1/users', method: 'POST')]
    #[AllowAnonymous]
    public function store(Request $req, Response $res)
    {
        $body = $req->getBody();
        if (!isset($body['name']) || $body['name'] === '') {
            return $res->json(['error' => 'Name is required'], 422);
        }
        $user = new User();
        $user->setName('ddd');
        $user->setEmail('eeee');
        $post = new Post();
        $post->setTitle('dddd');
        $post->setContent('ddd');
        $user->addPost($post);  // automatically links both sides
        $this->db->save($user);

        return $res->json(['user' => $user, 'body' => $body], 201);
    }
}
