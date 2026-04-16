<?php

namespace App\Entity;

use App\Repository\DecisionImpairRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DecisionImpairRepository::class)]
#[ORM\Table(name: 'decision_impair', indexes: [
    new ORM\Index(name: 'idx_decision_impair_action_impair', columns: ['action_impair']),
])]
class DecisionImpair
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'decisionImpair')]
    #[ORM\JoinColumn(name: 'id_manche', nullable: false, onDelete: 'CASCADE')]
    private ?Manche $manche = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_element_impair', nullable: false, onDelete: 'RESTRICT')]
    private ?Element $elementImpair = null;

    #[ORM\Column(name: 'action_impair', length: 16, enumType: ActionImpair::class)]
    private ActionImpair $action = ActionImpair::EN_ATTENTE;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_element_remplacant', nullable: true, onDelete: 'SET NULL')]
    private ?Element $elementRemplacant = null;

    #[ORM\Column(name: 'date_resolution', nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

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

        if ($manche !== null && $manche->getDecisionImpair() !== $this) {
            $manche->setDecisionImpair($this);
        }

        return $this;
    }

    public function getElementImpair(): ?Element
    {
        return $this->elementImpair;
    }

    public function setElementImpair(?Element $elementImpair): static
    {
        $this->elementImpair = $elementImpair;

        return $this;
    }

    public function getAction(): ActionImpair
    {
        return $this->action;
    }

    public function setAction(ActionImpair $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getElementRemplacant(): ?Element
    {
        return $this->elementRemplacant;
    }

    public function setElementRemplacant(?Element $elementRemplacant): static
    {
        $this->elementRemplacant = $elementRemplacant;

        return $this;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }
}
