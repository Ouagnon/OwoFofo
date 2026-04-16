<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[ORM\Table(name: 'theme')]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $nom = '';

    #[ORM\Column(length: 120, unique: true)]
    private string $slug = '';

    /**
     * @var Collection<int, Tournoi>
     */
    #[ORM\OneToMany(mappedBy: 'themeA', targetEntity: Tournoi::class)]
    private Collection $tournoisCommeThemeA;

    /**
     * @var Collection<int, Tournoi>
     */
    #[ORM\OneToMany(mappedBy: 'themeB', targetEntity: Tournoi::class)]
    private Collection $tournoisCommeThemeB;

    /**
     * @var Collection<int, Element>
     */
    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: Element::class)]
    private Collection $elements;

    public function __construct()
    {
        $this->tournoisCommeThemeA = new ArrayCollection();
        $this->tournoisCommeThemeB = new ArrayCollection();
        $this->elements = new ArrayCollection();
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = trim($slug);

        return $this;
    }

    /**
     * @return Collection<int, Tournoi>
     */
    public function getTournoisCommeThemeA(): Collection
    {
        return $this->tournoisCommeThemeA;
    }

    public function addTournoiCommeThemeA(Tournoi $tournoi): static
    {
        if (!$this->tournoisCommeThemeA->contains($tournoi)) {
            $this->tournoisCommeThemeA->add($tournoi);
            $tournoi->setThemeA($this);
        }

        return $this;
    }

    public function removeTournoiCommeThemeA(Tournoi $tournoi): static
    {
        if ($this->tournoisCommeThemeA->removeElement($tournoi)) {
            if ($tournoi->getThemeA() === $this) {
                $tournoi->setThemeA(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tournoi>
     */
    public function getTournoisCommeThemeB(): Collection
    {
        return $this->tournoisCommeThemeB;
    }

    public function addTournoiCommeThemeB(Tournoi $tournoi): static
    {
        if (!$this->tournoisCommeThemeB->contains($tournoi)) {
            $this->tournoisCommeThemeB->add($tournoi);
            $tournoi->setThemeB($this);
        }

        return $this;
    }

    public function removeTournoiCommeThemeB(Tournoi $tournoi): static
    {
        if ($this->tournoisCommeThemeB->removeElement($tournoi)) {
            if ($tournoi->getThemeB() === $this) {
                $tournoi->setThemeB(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Element>
     */
    public function getElements(): Collection
    {
        return $this->elements;
    }

    public function addElement(Element $element): static
    {
        if (!$this->elements->contains($element)) {
            $this->elements->add($element);
            $element->setTheme($this);
        }

        return $this;
    }

    public function removeElement(Element $element): static
    {
        if ($this->elements->removeElement($element)) {
            if ($element->getTheme() === $this) {
                $element->setTheme(null);
            }
        }

        return $this;
    }
}
