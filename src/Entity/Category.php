<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use App\Entity\Wish;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce nom de catégorie existe déjà.')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le nom de la catégorie est requis.')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $name = null;

    /** @var Collection<int, Wish> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Wish::class, cascade: ['persist'], orphanRemoval: false)]
    private Collection $wishes;

    public function __construct()
    {
        $this->wishes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) ($this->name ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, Wish>
     */
    public function getWishes(): Collection
    {
        return $this->wishes;
    }

    public function addWish(Wish $wish): static
    {
        if (!$this->wishes->contains($wish)) {
            $this->wishes->add($wish);
            $wish->setCategory($this);
        }
        return $this;
    }

    public function removeWish(Wish $wish): static
    {
        if ($this->wishes->removeElement($wish)) {
            if ($wish->getCategory() === $this) {
                $wish->setCategory(null);
            }
        }
        return $this;
    }
}
