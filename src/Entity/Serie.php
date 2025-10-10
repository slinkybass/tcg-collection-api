<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\Table(name: "cardSerie")]
#[ApiResource(
    normalizationContext: ['groups' => ['serie:read']],
    denormalizationContext: ['groups' => ['serie:write']]
)]
class Serie
{
    #[ORM\Id]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['serie:read', 'serie:write', 'set:read'])]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['serie:read', 'serie:write', 'set:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['serie:read', 'serie:write'])]
    private ?string $logo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['serie:read', 'serie:write'])]
    private ?\DateTime $releaseDate = null;

    #[ORM\OneToMany(targetEntity: Set::class, mappedBy: 'serie')]
    #[Groups(['serie:read'])]
    private Collection $cardSets;

    public function __construct()
    {
        $this->cardSets = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

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
