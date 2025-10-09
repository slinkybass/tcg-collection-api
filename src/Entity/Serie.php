<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\Table(name: "cardSerie")]
#[ApiResource]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idAPI = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $releaseDate = null;

    #[ORM\OneToMany(targetEntity: Set::class, mappedBy: 'serie')]
    private Collection $cardSets;

    public function __construct()
    {
        $this->cardSets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdAPI(): ?string
    {
        return $this->idAPI;
    }

    public function setIdAPI(string $idAPI): static
    {
        $this->idAPI = $idAPI;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getReleaseDate(): ?\DateTime
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTime $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    /**
     * @return Collection<int, Set>
     */
    public function getCardSets(): Collection
    {
        return $this->cardSets;
    }

    public function addCardSet(Set $cardSet): static
    {
        if (!$this->cardSets->contains($cardSet)) {
            $this->cardSets->add($cardSet);
            $cardSet->setSerie($this);
        }

        return $this;
    }

    public function removeCardSet(Set $cardSet): static
    {
        if ($this->cardSets->removeElement($cardSet)) {
            // set the owning side to null (unless already changed)
            if ($cardSet->getSerie() === $this) {
                $cardSet->setSerie(null);
            }
        }

        return $this;
    }
}
