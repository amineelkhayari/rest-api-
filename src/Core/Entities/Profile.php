<?php
namespace Core\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'profiles')]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): int
    {
        return $this->id;
    }

    #[ORM\Column(type: 'string', length: 255)]
    private string $bio;

    public function getBio(): string
    {
        return $this->bio;
    }

    // One-to-One: Profile -> User
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'profile')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): User
    {
        return $this->user;
    }
}
