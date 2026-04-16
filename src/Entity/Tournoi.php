<?php

namespace App\Entity;

use App\Repository\TournoiRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournoiRepository::class)]
#[ORM\Table(name: 'tournoi', indexes: [
    new ORM\Index(name: 'idx_tournoi_id_createur', columns: ['id_createur']),
    new ORM\Index(name: 'idx_tournoi_id_theme_a', columns: ['id_theme_a']),
    new ORM\Index(name: 'idx_tournoi_id_theme_b', columns: ['id_theme_b']),
])]
class Tournoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 120)]
    private string $nom = '';

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'url_couverture', length: 2048, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\Column(name: 'mode_tournoi', length: 32, enumType: ModeTournoi::class)]
    private ModeTournoi $mode = ModeTournoi::THEME_VS_THEME;

    #[ORM\ManyToOne(inversedBy: 'tournoisCrees')]
    #[ORM\JoinColumn(name: 'id_createur', nullable: true, onDelete: 'SET NULL')]
    private ?Joueur $createur = null;

    #[ORM\ManyToOne(inversedBy: 'tournoisCommeThemeA')]
    #[ORM\JoinColumn(name: 'id_theme_a', nullable: true, onDelete: 'SET NULL')]
    private ?Theme $themeA = null;

    #[ORM\ManyToOne(inversedBy: 'tournoisCommeThemeB')]
    #[ORM\JoinColumn(name: 'id_theme_b', nullable: true, onDelete: 'SET NULL')]
    private ?Theme $themeB = null;

    #[ORM\Column(name: 'taille_tableau_cible', type: Types::SMALLINT, nullable: true)]
    private ?int $tailleTableauCible = null;

    #[ORM\Column(name: 'brouillon', options: ['default' => true])]
    private bool $brouillon = true;

    #[ORM\Column(name: 'date_creation')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'date_modification')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Element>
     */
    #[ORM\OneToMany(mappedBy: 'tournoi', targetEntity: Element::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $elements;

    /**
     * @var Collection<int, Partie>
     */
    #[ORM\OneToMany(mappedBy: 'tournoi', targetEntity: Partie::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $parties;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
        $this->parties = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
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
        $this->touch();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->touch();

        return $this;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): static
    {
        $coverImageUrl = $coverImageUrl !== null ? trim($coverImageUrl) : null;
        $this->coverImageUrl = $coverImageUrl === '' ? null : $coverImageUrl;
        $this->touch();

        return $this;
    }

    public function getMode(): ModeTournoi
    {
        return $this->mode;
    }

    public function setMode(ModeTournoi $mode): static
    {
        $this->mode = $mode;
        $this->touch();

        return $this;
    }

    public function getCreateur(): ?Joueur
    {
        return $this->createur;
    }

    public function setCreateur(?Joueur $createur): static
    {
        $this->createur = $createur;
        $this->touch();

        return $this;
    }

    public function getThemeA(): ?Theme
    {
        return $this->themeA;
    }

    public function setThemeA(?Theme $themeA): static
    {
        $this->themeA = $themeA;
        $this->touch();

        return $this;
    }

    public function getThemeB(): ?Theme
    {
        return $this->themeB;
    }

    public function setThemeB(?Theme $themeB): static
    {
        $this->themeB = $themeB;
        $this->touch();

        return $this;
    }

    public function getTailleTableauCible(): ?int
    {
        return $this->tailleTableauCible;
    }

    public function setTailleTableauCible(?int $tailleTableauCible): static
    {
        $this->tailleTableauCible = $tailleTableauCible;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isBrouillon(): bool
    {
        return $this->brouillon;
    }

    public function setBrouillon(bool $brouillon): static
    {
        $this->brouillon = $brouillon;
        $this->touch();

        return $this;
    }

    public function touch(): static
    {
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, Element>
     */
    public function getElements(): Collection
    {
        return $this->elements;
    }

    /**
     * @return Element[]
     */
    public function getElementsActifs(): array
    {
        return array_values(array_filter(
            $this->elements->toArray(),
            static fn ($item): bool => $item instanceof Element && $item->isActif()
        ));
    }

    public function getNombreElementsActifs(): int
    {
        return count($this->getElementsActifs());
    }

    public function addElement(Element $element): static
    {
        if (!$this->elements->contains($element)) {
            $this->elements->add($element);
            $element->setTournoi($this);
            $this->touch();
        }

        return $this;
    }

    public function removeElement(Element $element): static
    {
        if ($this->elements->removeElement($element)) {
            if ($element->getTournoi() === $this) {
                $element->setTournoi(null);
            }

            $this->touch();
        }

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
            $partie->setTournoi($this);
            $this->touch();
        }

        return $this;
    }

    public function removePartie(Partie $partie): static
    {
        if ($this->parties->removeElement($partie)) {
            if ($partie->getTournoi() === $this) {
                $partie->setTournoi(null);
            }

            $this->touch();
        }

        return $this;
    }
}
