<?php

namespace App\Entity;

use App\Repository\PartieRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartieRepository::class)]
#[ORM\Table(name: 'partie', indexes: [
    new ORM\Index(name: 'idx_partie_id_joueur', columns: ['id_joueur']),
    new ORM\Index(name: 'idx_partie_id_tournoi', columns: ['id_tournoi']),
    new ORM\Index(name: 'idx_partie_etat_partie', columns: ['etat_partie']),
])]
class Partie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'parties')]
    #[ORM\JoinColumn(name: 'id_joueur', nullable: false, onDelete: 'CASCADE')]
    private ?Joueur $joueur = null;

    #[ORM\ManyToOne(inversedBy: 'parties')]
    #[ORM\JoinColumn(name: 'id_tournoi', nullable: false, onDelete: 'CASCADE')]
    private ?Tournoi $tournoi = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_vainqueur_final', nullable: true, onDelete: 'SET NULL')]
    private ?Element $vainqueurFinal = null;

    #[ORM\Column(name: 'etat_partie', length: 32, enumType: EtatPartie::class)]
    private EtatPartie $etat = EtatPartie::BROUILLON;

    #[ORM\Column(name: 'date_creation')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'date_modification')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'date_debut', nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'date_fin', nullable: true)]
    private ?DateTimeImmutable $finishedAt = null;

    #[ORM\Column(name: 'stats_consolidees', options: ['default' => false])]
    private bool $statsConsolidees = false;

    #[ORM\Column(name: 'classement_final_json', type: Types::JSON, nullable: true)]
    private ?array $classementFinal = null;

    /**
     * @var Collection<int, Manche>
     */
    #[ORM\OneToMany(mappedBy: 'partie', targetEntity: Manche::class, orphanRemoval: true, cascade: ['remove'])]
    private Collection $manches;

    public function __construct()
    {
        $this->manches = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJoueur(): ?Joueur
    {
        return $this->joueur;
    }

    public function setJoueur(?Joueur $joueur): static
    {
        $this->joueur = $joueur;
        $this->touch();

        return $this;
    }

    public function getTournoi(): ?Tournoi
    {
        return $this->tournoi;
    }

    public function setTournoi(?Tournoi $tournoi): static
    {
        $this->tournoi = $tournoi;
        $this->touch();

        return $this;
    }

    public function getVainqueurFinal(): ?Element
    {
        return $this->vainqueurFinal;
    }

    public function setVainqueurFinal(?Element $vainqueurFinal): static
    {
        $this->vainqueurFinal = $vainqueurFinal;
        $this->touch();

        return $this;
    }

    public function getEtat(): EtatPartie
    {
        return $this->etat;
    }

    public function setEtat(EtatPartie $etat): static
    {
        $this->etat = $etat;
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

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        $this->touch();

        return $this;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        $this->touch();

        return $this;
    }

    public function touch(): static
    {
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function isStatsConsolidees(): bool
    {
        return $this->statsConsolidees;
    }

    public function setStatsConsolidees(bool $statsConsolidees): static
    {
        $this->statsConsolidees = $statsConsolidees;
        $this->touch();

        return $this;
    }

    /**
     * @return int[]
     */
    public function getClassementFinal(): array
    {
        if (!is_array($this->classementFinal)) {
            return [];
        }

        $ids = [];
        foreach ($this->classementFinal as $rawId) {
            $id = (int) $rawId;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param int[] $classementFinal
     */
    public function setClassementFinal(array $classementFinal): static
    {
        $ids = [];
        foreach ($classementFinal as $rawId) {
            $id = (int) $rawId;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        $this->classementFinal = $ids;
        $this->touch();

        return $this;
    }

    /**
     * @return Collection<int, Manche>
     */
    public function getManches(): Collection
    {
        return $this->manches;
    }

    public function addManche(Manche $manche): static
    {
        if (!$this->manches->contains($manche)) {
            $this->manches->add($manche);
            $manche->setPartie($this);
            $this->touch();
        }

        return $this;
    }

    public function removeManche(Manche $manche): static
    {
        if ($this->manches->removeElement($manche)) {
            if ($manche->getPartie() === $this) {
                $manche->setPartie(null);
            }

            $this->touch();
        }

        return $this;
    }
}
