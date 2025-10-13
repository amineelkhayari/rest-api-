<?php
namespace Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "users")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    #[ORM\Column(type: "string", length: 150)]
    private string $email;

    // One-to-One: User <-> Profile
    #[ORM\OneToOne(targetEntity: Profile::class, mappedBy: "user", cascade: ["persist", "remove"])]
    private ?Profile $profile = null;

    // One-to-Many: User -> Posts
    #[ORM\OneToMany(mappedBy: "author", targetEntity: Post::class, cascade: ["persist", "remove"])]
    private Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }
}
