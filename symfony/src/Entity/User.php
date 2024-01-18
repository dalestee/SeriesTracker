<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: "user", uniqueConstraints: [
    new ORM\UniqueConstraint(name: "UNIQ_USER_NAME", columns: ["name"]),
    new ORM\UniqueConstraint(name: "UNIQ_USER_EMAIL", columns: ["email"]),
    new ORM\UniqueConstraint(name: "IDX_USER_COUNTRY_ID", columns: ["country_id"]),
])]

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'This name is already used')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 128, nullable: false, unique: true)]
    private $name;

    #[ORM\Column(name: "email", type: "string", length: 128, nullable: false)]
    private $email;

    #[ORM\Column(name: "password", type: "string", length: 128, nullable: false)]
    private $password;

    #[ORM\Column(name: "register_date", type: "datetime", nullable: true)]
    private $registerDate;

    #[ORM\Column(name: "admin", type: "integer", nullable: false)]
    private $admin = 0;

    #[ORM\Column(name: "ban", type: "string", length: 255, nullable: true)]
    private $ban;

    #[ORM\Column(name: "user_id", type: "string", length: 128, nullable: true)]
    private $userId;

    #[ORM\Column(name: "lastConnexion", type: "datetime", nullable: true)]
    private $lastConnexion;

    #[ORM\ManyToMany(targetEntity: "User", inversedBy: "followers")]
    #[ORM\JoinTable(
        name: "user_followers",
        joinColumns: [new ORM\JoinColumn(name: "user_follower", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "user_followed", referencedColumnName: "id")]
    )]
    private $following;

    #[ORM\ManyToMany(targetEntity: "User", mappedBy: "following")]
    private $followers;


    #[ORM\ManyToOne(targetEntity: "Country")]
    #[ORM\JoinColumn(name: "country_id", referencedColumnName: "id")]
    private $country;

    #[ORM\ManyToMany(targetEntity: "Series", inversedBy: "user")]
    #[ORM\JoinTable(
        name: "user_series",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "series_id", referencedColumnName: "id")]
    )]
    private $series = array();

    #[ORM\ManyToMany(targetEntity: "Episode", inversedBy: "user")]
    #[ORM\JoinTable(
        name: "user_episode",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "episode_id", referencedColumnName: "id")]
    )]
    private $episode = array();

    #[ORM\OneToMany(mappedBy: "user", targetEntity: "Rating")]
    private $ratings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->series = new \Doctrine\Common\Collections\ArrayCollection();
        $this->episode = new \Doctrine\Common\Collections\ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastConnexion()
    {
        return $this->lastConnexion;
    }

    public function setLastConnexion($lastConnexion): self
    {
        $this->lastConnexion = $lastConnexion;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRegisterDate()
    {
        return $this->registerDate;
    }

    public function registerDateToString()
    {
        if ($this->getRegisterDate()) {
            return $this->getRegisterDate()->format('Y-m-d H:i:s');
        }
        return null;
    }

    public function setRegisterDate($registerDate): self
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin > 0;
    }

    public function isSuperAdmin(): ?bool
    {
        return $this->admin > 1;
    }

    public function getRole(): ?string
    {
        switch ($this->admin) {
            case 1:
                return 'Administrateur';
            case 2:
                return 'Super-Administrateur';
            default:
                return 'Utilisateur';
        }
    }

    public function setAdmin(int $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Series $series): self
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
        }

        return $this;
    }

    public function removeSeries(Series $series): self
    {
        $this->series->removeElement($series);

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisode(): Collection
    {
        return $this->episode;
    }

    public function addEpisode(Episode $episode): self
    {
        if (!$this->episode->contains($episode)) {
            $this->episode->add($episode);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): self
    {
        $this->episode->removeElement($episode);

        return $this;
    }



    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }
    public function getRoles(): array
    {
        $roles = [];
        if ($this->isSuperAdmin()) {
            $roles += ['ROLE_SUPER_ADMIN'];
        } elseif ($this->isAdmin()) {
            $roles += ['ROLE_ADMIN'];
        } else {
            $roles += ['ROLE_USER'];
        }
        return ($roles);
    }
    public function eraseCredentials(): void
    {
    }
    public function isfollowingSeries(Series $series): bool
    {
        return $this->series->contains($series);
    }

    public function isEpisodeViewed(Episode $episode): bool
    {
        return $this->episode->contains($episode);
    }

    public function getAdmin(): ?int
    {
        return $this->admin;
    }

    public function getBan(): ?string
    {
        return $this->ban;
    }
    /**
     * @return Collection<int, User>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(User $following): static
    {
        if (!$this->following->contains($following)) {
            $this->following->add($following);
        }

        return $this;
    }

    public function removeFollowing(User $following): static
    {
        $this->following->removeElement($following);

        return $this;
    }
    public function isFollowing(User $user): bool
    {
        return $this->getFollowing()->contains($user);
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(User $follower): static
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
            $follower->addFollowing($this);
        }

        return $this;
    }

    public function removeFollower(User $follower): static
    {
        if ($this->followers->removeElement($follower)) {
            $follower->removeFollowing($this);
        }

        return $this;
    }
}
