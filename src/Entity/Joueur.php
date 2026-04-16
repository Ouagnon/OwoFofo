<?php

namespace App\Entity;

use App\Repository\JoueurRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: JoueurRepository::class)]
#[ORM\Table(name: 'joueur')]
class Joueur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 80, unique: true)]
    private string $nom = '';

    #[ORM\Column(name: 'mot_de_passe_hash', length: 255)]
    private string $motDePasseHash = '';

    #[ORM\Column(name: 'date_creation')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'activer_repechage', options: ['default' => true])]
    private bool $activerRepechage = true;

    #[ORM\Column(name: 'activer_classement_perdants', options: ['default' => true])]
    private bool $activerClassementPerdants = true;

    /**
     * @var Collection<int, Partie>
     */
    #[ORM\OneToMany(mappedBy: 'joueur', targetEntity: Partie::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $parties;

    /**
     * @var Collection<int, Tournoi>
     */
    #[ORM\OneToMany(mappedBy: 'createur', targetEntity: Tournoi::class)]
    private Collection $tournoisCrees;

    public function __construct()
    {
        $this->parties = new ArrayCollection();
        $this->tournoisCrees = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = trim($nom);

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getMotDePasseHash(): string
    {
        return $this->motDePasseHash;
    }

    public function isActiverRepechage(): bool
    {
        return $this->activerRepechage;
    }

    public function setActiverRepechage(bool $activerRepechage): static
    {
        $this->activerRepechage = $activerRepechage;

        return $this;
    }

    public function isActiverClassementPerdants(): bool
    {
        return $this->activerClassementPerdants;
    }

    public function setActiverClassementPerdants(bool $activerClassementPerdants): static
    {
        $this->activerClassementPerdants = $activerClassementPerdants;

        return $this;
    }

    public function setMotDePasseHash(string $motDePasseHash): static
    {
        $this->motDePasseHash = $motDePasseHash;

        return $this;
    }

    /**
     * @return Collection<int, Partie>
     */
    public function getParties(): Collection
    {
        return $this->parties;
    }

    public function addPartie(Partie $partie): static
    {
        if (!$this->parties->contains($partie)) {
            $this->parties->add($partie);
            $partie->setJoueur($this);
        }

        return $this;
    }

    public function removePartie(Partie $partie): static
    {
        if ($this->parties->removeElement($partie)) {
            if ($partie->getJoueur() === $this) {
                $partie->setJoueur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tournoi>
     */
    public function getTournoisCrees(): Collection
    {
        return $this->tournoisCrees;
    }

    public function addTournoiCree(Tournoi $tournoi): static
    {
        if (!$this->tournoisCrees->contains($tournoi)) {
            $this->tournoisCrees->add($tournoi);
            $tournoi->setCreateur($this);
        }

        return $this;
    }

    public function removeTournoiCree(Tournoi $tournoi): static
    {
        if ($this->tournoisCrees->removeElement($tournoi)) {
            if ($tournoi->getCreateur() === $this) {
                $tournoi->setCreateur(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->nom;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->motDePasseHash;
    }
}
