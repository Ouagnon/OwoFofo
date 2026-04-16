<?php

namespace App\Entity;

use App\Repository\MancheRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MancheRepository::class)]
#[ORM\Table(name: 'manche', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_manche_id_partie_numero', columns: ['id_partie', 'numero']),
], indexes: [
    new ORM\Index(name: 'idx_manche_id_partie', columns: ['id_partie']),
    new ORM\Index(name: 'idx_manche_type_manche', columns: ['type_manche']),
    new ORM\Index(name: 'idx_manche_etat_manche', columns: ['etat_manche']),
    new ORM\Index(name: 'idx_manche_mode_appariement', columns: ['mode_appariement']),
])]
class Manche
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'manches')]
    #[ORM\JoinColumn(name: 'id_partie', nullable: false, onDelete: 'CASCADE')]
    private ?Partie $partie = null;

    #[ORM\Column(name: 'numero', type: Types::SMALLINT)]
    private int $numero = 1;

    #[ORM\Column(name: 'type_manche', length: 8, enumType: TypeManche::class)]
    private TypeManche $type = TypeManche::M64;

    #[ORM\Column(name: 'etat_manche', length: 32, enumType: EtatManche::class)]
    private EtatManche $etat = EtatManche::EN_PREPARATION;

    #[ORM\Column(name: 'mode_appariement', length: 32, enumType: ModeAppariement::class)]
    private ModeAppariement $strategieAppariement = ModeAppariement::INTER_THEME_MAX;

    #[ORM\Column(name: 'date_creation')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'date_fin', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    /**
     * @var Collection<int, Duel>
     */
    #[ORM\OneToMany(mappedBy: 'manche', targetEntity: Duel::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $duels;

    #[ORM\OneToOne(mappedBy: 'manche', targetEntity: DecisionImpair::class, orphanRemoval: true, cascade: ['remove'])]
    private ?DecisionImpair $decisionImpair = null;

    /**
     * @var Collection<int, Repechage>
     */
    #[ORM\OneToMany(mappedBy: 'manche', targetEntity: Repechage::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $repechages;

    /**
     * @var Collection<int, ClassementPerdant>
     */
    #[ORM\OneToMany(mappedBy: 'manche', targetEntity: ClassementPerdant::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $classementsPerdants;

    public function __construct()
    {
        $this->duels = new ArrayCollection();
        $this->repechages = new ArrayCollection();
        $this->classementsPerdants = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartie(): ?Partie
    {
        return $this->partie;
    }

    public function setPartie(?Partie $partie): static
    {
        $this->partie = $partie;

        return $this;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getType(): TypeManche
    {
        return $this->type;
    }

    public function setType(TypeManche $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEtat(): EtatManche
    {
        return $this->etat;
    }

    public function setEtat(EtatManche $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getStrategieAppariement(): ModeAppariement
    {
        return $this->strategieAppariement;
    }

    public function setStrategieAppariement(ModeAppariement $strategieAppariement): static
    {
        $this->strategieAppariement = $strategieAppariement;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, Duel>
     */
    public function getDuels(): Collection
    {
        return $this->duels;
    }

    public function addDuel(Duel $duel): static
    {
        if (!$this->duels->contains($duel)) {
            $this->duels->add($duel);
            $duel->setManche($this);
        }

        return $this;
    }

    public function removeDuel(Duel $duel): static
    {
        if ($this->duels->removeElement($duel)) {
            if ($duel->getManche() === $this) {
                $duel->setManche(null);
            }
        }

        return $this;
    }

    public function getDecisionImpair(): ?DecisionImpair
    {
        return $this->decisionImpair;
    }

    public function setDecisionImpair(?DecisionImpair $decisionImpair): static
    {
        if ($decisionImpair === null && $this->decisionImpair !== null) {
            $this->decisionImpair->setManche(null);
        }

        if ($decisionImpair !== null && $decisionImpair->getManche() !== $this) {
            $decisionImpair->setManche($this);
        }

        $this->decisionImpair = $decisionImpair;

        return $this;
    }

    /**
     * @return Collection<int, Repechage>
     */
    public function getRepechages(): Collection
    {
        return $this->repechages;
    }

    public function addRepechage(Repechage $repechage): static
    {
        if (!$this->repechages->contains($repechage)) {
            $this->repechages->add($repechage);
            $repechage->setManche($this);
        }

        return $this;
    }

    public function removeRepechage(Repechage $repechage): static
    {
        if ($this->repechages->removeElement($repechage)) {
            if ($repechage->getManche() === $this) {
                $repechage->setManche(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClassementPerdant>
     */
    public function getClassementsPerdants(): Collection
    {
        return $this->classementsPerdants;
    }

    public function addClassementPerdant(ClassementPerdant $classementPerdant): static
    {
        if (!$this->classementsPerdants->contains($classementPerdant)) {
            $this->classementsPerdants->add($classementPerdant);
            $classementPerdant->setManche($this);
        }

        return $this;
    }

    public function removeClassementPerdant(ClassementPerdant $classementPerdant): static
    {
        if ($this->classementsPerdants->removeElement($classementPerdant)) {
            if ($classementPerdant->getManche() === $this) {
                $classementPerdant->setManche(null);
            }
        }

        return $this;
    }
}
