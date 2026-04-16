<?php

namespace App\Entity;

use App\Repository\DuelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DuelRepository::class)]
#[ORM\Table(name: 'duel', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_duel_id_manche_ordre', columns: ['id_manche', 'ordre']),
], indexes: [
    new ORM\Index(name: 'idx_duel_id_manche', columns: ['id_manche']),
    new ORM\Index(name: 'idx_duel_id_vainqueur', columns: ['id_vainqueur']),
    new ORM\Index(name: 'idx_duel_etat_duel', columns: ['etat_duel']),
    new ORM\Index(name: 'idx_duel_id_element_a', columns: ['id_element_a']),
    new ORM\Index(name: 'idx_duel_id_element_b', columns: ['id_element_b']),
])]
class Duel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'duels')]
    #[ORM\JoinColumn(name: 'id_manche', nullable: false, onDelete: 'CASCADE')]
    private ?Manche $manche = null;

    #[ORM\Column(name: 'ordre', type: Types::SMALLINT)]
    private int $ordre = 1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_element_a', nullable: false, onDelete: 'RESTRICT')]
    private ?Element $elementA = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_element_b', nullable: false, onDelete: 'RESTRICT')]
    private ?Element $elementB = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_vainqueur', nullable: true, onDelete: 'SET NULL')]
    private ?Element $vainqueur = null;

    #[ORM\Column(name: 'etat_duel', length: 16, enumType: EtatDuel::class)]
    private EtatDuel $etat = EtatDuel::A_JOUER;

    #[ORM\Column(name: 'type_confrontation', length: 16, enumType: TypeConfrontation::class)]
    private TypeConfrontation $typeConfrontation = TypeConfrontation::LIBRE;

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

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getElementA(): ?Element
    {
        return $this->elementA;
    }

    public function setElementA(?Element $elementA): static
    {
        $this->elementA = $elementA;

        return $this;
    }

    public function getElementB(): ?Element
    {
        return $this->elementB;
    }

    public function setElementB(?Element $elementB): static
    {
        $this->elementB = $elementB;

        return $this;
    }

    public function getVainqueur(): ?Element
    {
        return $this->vainqueur;
    }

    public function setVainqueur(?Element $vainqueur): static
    {
        $this->vainqueur = $vainqueur;

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

    public function getTypeConfrontation(): TypeConfrontation
    {
        return $this->typeConfrontation;
    }

    public function setTypeConfrontation(TypeConfrontation $typeConfrontation): static
    {
        $this->typeConfrontation = $typeConfrontation;

        return $this;
    }

    public function isTermine(): bool
    {
        return $this->etat === EtatDuel::TERMINE;
    }
}
