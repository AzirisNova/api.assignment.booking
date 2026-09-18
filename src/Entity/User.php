<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
final class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'full_name', type: 'string', length: 120)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = 'client';

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'api_token', type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $apiToken = null;

    public function __construct(string $email, string $passwordHash, string $fullName)
    {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->fullName = $fullName;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    public function eraseCredentials(): void
    {
    }
}
