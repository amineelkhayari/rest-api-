<?php

namespace App\Controllers;

use Core\Entities\Amine;  // ✅ Add this line
use Core\Entities\FakeTable;  // ✅ Add this line
use Core\Entities\Post;  // ✅ Add this line
use Core\Entities\User;  // ✅ Add this line
use Core\Database;
use Core\DoctrineOrmFactory;
use Core\Request;
use Core\Response;
use \App\Helpers\Attributes\AllowAnonymous;
use \App\Helpers\Attributes\ApiController;
use \App\Helpers\Attributes\Authorize;
use \App\Helpers\Attributes\Route;

#[ApiController]
// #[AllowAnonymous]
#[Authorize([], 'accident-api')]
class HomeController
{
    private Database $db;

    public function __construct(?Database $db)
    {
        $this->db = $db ?? new Database();
    }

    #[Route(path: '/health', method: 'GET')]
    #[Authorize(['accident.basique', 'accident.admin', 'accident.superadmin'])]
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
        //         $users = $this->em->getRepository(Amine::class)->findAll();
        //          // Format for JSON response
        // $userData = array_map(fn(Amine $u) => [
        //     'id' => $u->getId(),
        //     'name' => $u->getName(),
        // ], $users);
         $users = $this->db->getData(User::class);

            // Convert Doctrine entities to a simple array for JSON
            $userData = array_map(fn($u) => [
                'name' => $u->getEmail(),
            ], $users);

        // ✅ Create and save the entity
        // $amine = new Amine();
        // $amine->setName("amine");

        // $this->db->save($amine);
        //  $user = new User();
        // $user->setName("ddd");
        // $user->setEmail("eeee");

        //     $post = new Post();
        //     $post->setTitle("dddd");
        //     $post->setContent("ddd");
        //     $user->addPost($post); // automatically links both sides
        $user = new FakeTable();
        $user->name = 'amine';

        $this->db->save($user);
        return $res->json([
            'status' => 'ok',
            'data' => 're',
            'er' => $userData
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
