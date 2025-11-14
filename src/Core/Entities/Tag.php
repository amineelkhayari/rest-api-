<?php
namespace Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tags')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    public function getId(): int
    {
        return $this->id;
    }

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    public function getName(): int
    {
        return $this->name;
    }

    // Many-to-Many inverse: Tag -> Post
    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'tags')]
    private Collection $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }
}
