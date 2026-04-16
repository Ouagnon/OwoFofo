<?php

namespace App\Entity;

use App\Repository\RepechageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RepechageRepository::class)]
#[ORM\Table(name: 'repechage', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_repechage_id_manche_ordre', columns: ['id_manche', 'ordre']),
], indexes: [
    new ORM\Index(name: 'idx_repechage_id_manche', columns: ['id_manche']),
    new ORM\Index(name: 'idx_repechage_id_theme', columns: ['id_theme']),
    new ORM\Index(name: 'idx_repechage_etat_duel', columns: ['etat_duel']),
])]
class Repechage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'repechages')]
    #[ORM\JoinColumn(name: 'id_manche', nullable: false, onDelete: 'CASCADE')]
    private ?Manche $manche = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_theme', nullable: true, onDelete: 'SET NULL')]
    private ?Theme $theme = null;

    #[ORM\Column(name: 'ordre', type: Types::SMALLINT)]
    private int $ordre = 1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_perdant', nullable: false, onDelete: 'RESTRICT')]
    private ?Element $perdant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_vainqueur_cible', nullable: false, onDelete: 'RESTRICT')]
    private ?Element $vainqueurCible = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_vainqueur_final', nullable: true, onDelete: 'SET NULL')]
    private ?Element $vainqueurFinal = null;

    #[ORM\Column(name: 'etat_duel', length: 16, enumType: EtatDuel::class)]
    private EtatDuel $etat = EtatDuel::A_JOUER;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getManche(): ?Manche
    {
        return $this->manche;
    }

    public function setManche(?Manche $manche): static
    {
        $this->manche = $manche;

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

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getPerdant(): ?Element
    {
        return $this->perdant;
    }

    public function setPerdant(?Element $perdant): static
    {
        $this->perdant = $perdant;

        return $this;
    }

    public function getVainqueurCible(): ?Element
    {
        return $this->vainqueurCible;
    }

    public function setVainqueurCible(?Element $vainqueurCible): static
    {
        $this->vainqueurCible = $vainqueurCible;

        return $this;
    }

    public function getVainqueurFinal(): ?Element
    {
        return $this->vainqueurFinal;
    }

    public function setVainqueurFinal(?Element $vainqueurFinal): static
    {
        $this->vainqueurFinal = $vainqueurFinal;

        return $this;
    }

    public function getEtat(): EtatDuel
    {
        return $this->etat;
    }

    public function setEtat(EtatDuel $etat): static
    {
        $this->etat = $etat;

        return $this;
    }
}
