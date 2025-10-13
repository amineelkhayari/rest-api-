<?php
namespace Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "posts")]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 200)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $content;

    // Many-to-One: Post -> User
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "posts")]
    #[ORM\JoinColumn(name: "author_id", referencedColumnName: "id")]
    private ?User $author = null;

    // Many-to-Many: Post <-> Tag
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: "posts")]
    #[ORM\JoinTable(name: "post_tags")]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}
