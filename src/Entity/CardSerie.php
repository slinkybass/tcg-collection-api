<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CardSerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: CardSerieRepository::class)]
#[ORM\Table(name: "cardSerie")]
#[ApiResource(
    shortName: 'Serie',
    normalizationContext: ['groups' => ['cardSerie:read']],
    denormalizationContext: ['groups' => ['cardSerie:write']]
)]
class CardSerie
{
    #[ORM\Id]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['cardSerie:read', 'cardSerie:write', 'cardSet:read', 'card:read'])]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['cardSerie:read', 'cardSerie:write', 'cardSet:read', 'card:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['cardSerie:read', 'cardSerie:write'])]
    private ?string $logo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['cardSerie:read', 'cardSerie:write'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    private ?\DateTime $releaseDate = null;

    #[ORM\OneToMany(targetEntity: CardSet::class, mappedBy: 'cardSerie')]
    #[Groups(['cardSerie:read'])]
    #[SerializedName('sets')]
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
     * @return Collection<int, CardSet>
     */
    public function getCardSets(): Collection
    {
        return $this->cardSets;
    }

    public function addCardSet(CardSet $cardSet): static
    {
        if (!$this->cardSets->contains($cardSet)) {
            $this->cardSets->add($cardSet);
            $cardSet->setCardSerie($this);
        }

        return $this;
    }

    public function removeCardSet(CardSet $cardSet): static
    {
        if ($this->cardSets->removeElement($cardSet)) {
            // set the owning side to null (unless already changed)
            if ($cardSet->getCardSerie() === $this) {
                $cardSet->setCardSerie(null);
            }
        }

        return $this;
    }
}
