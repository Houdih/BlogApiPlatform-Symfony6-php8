<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read:users']],
    denormalizationContext: ['groups' => ['write:user']],
    paginationItemsPerPage: 10,
    paginationMaximumItemsPerPage: 20,
    paginationClientItemsPerPage: true,
    collectionOperations: [
        'get',
        "post" 
    ],
    itemOperations: [
        'get',
        "put" => [
            "security" => "is_granted('USER_EDIT', object)",
            "security_message" => "Sorry, but you are not the actual User owner.",
        ],
        'delete' => [
            "security" => "is_granted('USER_DELETE', object)",
            "security_message" => "Sorry, but you are not the actual User owner.",
        ],
    ],
)]

class User implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:users', 'read:article'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true, name: 'email', type: 'string')]
    #[Groups(['read:users', 'write:user', 'read:article'])]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['read:users', 'write:user', 'read:article', 'read:articles'])]
    private ?string $pseudo = null;

    #[ORM\Column]
    #[Groups(['read:users'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    
    #[SerializedName('password')]
    #[Regex(
        pattern:"/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{7,}/",
        message: "Le mot de passe doit contenir minimum 7 caractères dont un chiffre, une lettre majuscule et une lettre minuscule"
    )]
    #[Groups(['write:user'])]
    private ?string $plainPassword = null;

    
    #[SerializedName('confirmed password')]
    #[Regex(
        pattern:"/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{7,}/",
        message: "Le mot de passe doit contenir minimum 7 caractères dont un chiffre, une lettre majuscule et une lettre minuscule"
    )]
    #[Expression(
        "this.getPlainPassword() === this.getPasswordBis()",
        message: "Passwords does not match"
    )]
    #[Groups(['write:user'])]
    private ?string $passwordBis = null;

    #[ORM\OneToMany(mappedBy: 'authorArticle', targetEntity: Article::class)]
    #[Groups(['read:users'])]
    private Collection $articles;

    #[ORM\OneToMany(mappedBy: 'authorComment', targetEntity: Comment::class)]
    private Collection $comments;


    public function __construct() {
        $this->articles = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    // create setter for initialize with 'id' the payload
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_ADMIN';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getPasswordBis(): ?string
    {
        return $this->passwordBis;
    }

    public function setPasswordBis(string $passwordBis): self
    {
        $this->passwordBis = $passwordBis;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setAuthorArticle($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getAuthorArticle() === $this) {
                $article->setAuthorArticle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuthorComment($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getAuthorComment() === $this) {
                $comment->setAuthorComment(null);
            }
        }

        return $this;
    }

    public static function createFromPayload($id, array $payload)
    {
        $user = new user();
        $user->setId($id);
        $user->setRoles($payload['roles']);
        return $user;
    }
}
