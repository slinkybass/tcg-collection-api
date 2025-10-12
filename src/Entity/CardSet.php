<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Controller\Api\CardSetOpenAction;
use App\Repository\CardSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: CardSetRepository::class)]
#[ORM\Table(name: "cardSet")]
#[ApiResource(
    shortName: 'Set',
    normalizationContext: ['groups' => ['cardSet:read']],
    denormalizationContext: ['groups' => ['cardSet:write']],
    operations: [
        new \ApiPlatform\Metadata\Get(),
        new \ApiPlatform\Metadata\GetCollection(),
        new \ApiPlatform\Metadata\Post(),
        new \ApiPlatform\Metadata\Patch(),
        new \ApiPlatform\Metadata\Delete(),
        new \ApiPlatform\Metadata\Get(
            name: 'cardSet_open',
            uriTemplate: '/sets/{id}/open',
            controller: CardSetOpenAction::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Opens a CardSet and returns a list of obtained Cards.'),
            read: true,
            write: false,
        ),
    ]
)]
class CardSet
{
    #[ORM\Id]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['cardSet:read', 'cardSet:write', 'cardSerie:read', 'card:read'])]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['cardSet:read', 'cardSet:write', 'cardSerie:read', 'card:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['cardSet:read', 'cardSet:write'])]
    private ?string $logo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['cardSet:read', 'cardSet:write'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    private ?\DateTime $releaseDate = null;

    #[ORM\ManyToOne(inversedBy: 'cardSets')]
    #[ORM\JoinColumn(name: 'cardSerie_id', referencedColumnName: 'id')]
    #[Groups(['cardSet:read', 'cardSet:write', 'card:read'])]
    #[SerializedName('serie')]
    private ?CardSerie $cardSerie = null;

    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'cardSet')]
    #[Groups(['cardSet:read'])]
    private Collection $cards;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
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

    public function getCardSerie(): ?CardSerie
    {
        return $this->cardSerie;
    }

    public function setCardSerie(?CardSerie $cardSerie): static
    {
        $this->cardSerie = $cardSerie;

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setCardSet($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            // set the owning side to null (unless already changed)
            if ($card->getCardSet() === $this) {
                $card->setCardSet(null);
            }
        }

        return $this;
    }
}
