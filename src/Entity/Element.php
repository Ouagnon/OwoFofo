<?php

namespace App\Entity;

use App\Repository\ElementRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ElementRepository::class)]
#[ORM\Table(name: 'element', indexes: [
    new ORM\Index(name: 'idx_element_id_tournoi', columns: ['id_tournoi']),
    new ORM\Index(name: 'idx_element_id_theme', columns: ['id_theme']),
    new ORM\Index(name: 'idx_element_seed', columns: ['seed']),
])]
class Element
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'titre', length: 255)]
    private string $titre = '';

    #[ORM\Column(name: 'url_media', length: 2048)]
    private string $mediaUrl = '';

    #[ORM\Column(name: 'type_media', length: 16, enumType: TypeMedia::class)]
    private TypeMedia $mediaType = TypeMedia::IMAGE;

    #[ORM\ManyToOne(inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'id_tournoi', nullable: false, onDelete: 'CASCADE')]
    private ?Tournoi $tournoi = null;

    #[ORM\ManyToOne(inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'id_theme', nullable: true, onDelete: 'SET NULL')]
    private ?Theme $theme = null;

    #[ORM\Column(name: 'seed', type: Types::SMALLINT, nullable: true)]
    private ?int $seed = null;

    #[ORM\Column(name: 'actif', options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(name: 'duels_joues_cumules', type: Types::INTEGER, options: ['default' => 0])]
    private int $duelsJouesCumules = 0;

    #[ORM\Column(name: 'duels_gagnes_cumules', type: Types::INTEGER, options: ['default' => 0])]
    private int $duelsGagnesCumules = 0;

    #[ORM\Column(name: 'tournois_gagnes_cumules', type: Types::INTEGER, options: ['default' => 0])]
    private int $tournoisGagnesCumules = 0;

    #[ORM\Column(name: 'date_creation')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = trim($titre);

        return $this;
    }

    public function getMediaUrl(): string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(string $mediaUrl): static
    {
        $this->mediaUrl = trim($mediaUrl);

        return $this;
    }

    public function getMediaType(): TypeMedia
    {
        return $this->mediaType;
    }

    public function setMediaType(TypeMedia $mediaType): static
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getTournoi(): ?Tournoi
    {
        return $this->tournoi;
    }

    public function setTournoi(?Tournoi $tournoi): static
    {
        $this->tournoi = $tournoi;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getSeed(): ?int
    {
        return $this->seed;
    }

    public function setSeed(?int $seed): static
    {
        $this->seed = $seed;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDuelsJouesCumules(): int
    {
        return $this->duelsJouesCumules;
    }

    public function incrementerDuelsJoues(int $increment = 1): static
    {
        if ($increment > 0) {
            $this->duelsJouesCumules += $increment;
        }

        return $this;
    }

    public function getDuelsGagnesCumules(): int
    {
        return $this->duelsGagnesCumules;
    }

    public function incrementerDuelsGagnes(int $increment = 1): static
    {
        if ($increment > 0) {
            $this->duelsGagnesCumules += $increment;
        }

        return $this;
    }

    public function getTournoisGagnesCumules(): int
    {
        return $this->tournoisGagnesCumules;
    }

    public function incrementerTournoisGagnes(int $increment = 1): static
    {
        if ($increment > 0) {
            $this->tournoisGagnesCumules += $increment;
        }

        return $this;
    }

    public function isImage(): bool
    {
        return $this->mediaType === TypeMedia::IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->mediaType === TypeMedia::VIDEO;
    }

    public function setMedia(string $mediaUrl, TypeMedia $mediaType): static
    {
        $this->mediaUrl = trim($mediaUrl);
        $this->mediaType = $mediaType;

        return $this;
    }
}
