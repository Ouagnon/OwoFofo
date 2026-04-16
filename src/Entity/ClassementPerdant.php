<?php

namespace App\Entity;

use App\Repository\ClassementPerdantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassementPerdantRepository::class)]
#[ORM\Table(name: 'classement_perdant', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_classement_perdant_id_manche_rang', columns: ['id_manche', 'rang']),
    new ORM\UniqueConstraint(name: 'uniq_classement_perdant_id_manche_id_element', columns: ['id_manche', 'id_element']),
], indexes: [
    new ORM\Index(name: 'idx_classement_perdant_id_theme', columns: ['id_theme']),
])]
class ClassementPerdant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'classementsPerdants')]
    #[ORM\JoinColumn(name: 'id_manche', nullable: false, onDelete: 'CASCADE')]
    private ?Manche $manche = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_element', nullable: false, onDelete: 'RESTRICT')]
    private ?Element $element = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_theme', nullable: true, onDelete: 'SET NULL')]
    private ?Theme $theme = null;

    #[ORM\Column(name: 'rang', type: Types::SMALLINT)]
    private int $rang = 1;

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

    public function getElement(): ?Element
    {
        return $this->element;
    }

    public function setElement(?Element $element): static
    {
        $this->element = $element;

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

    public function getRang(): int
    {
        return $this->rang;
    }

    public function setRang(int $rang): static
    {
        $this->rang = $rang;

        return $this;
    }
}
