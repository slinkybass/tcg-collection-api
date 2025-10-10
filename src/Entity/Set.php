<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Controller\Api\SetOpenAction;
use App\Repository\SetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SetRepository::class)]
#[ORM\Table(name: "cardSet")]
#[ApiResource(
    normalizationContext: ['groups' => ['set:read']],
    denormalizationContext: ['groups' => ['set:write']],
    operations: [
        new \ApiPlatform\Metadata\Get(),
        new \ApiPlatform\Metadata\GetCollection(),
        new \ApiPlatform\Metadata\Post(),
        new \ApiPlatform\Metadata\Patch(),
        new \ApiPlatform\Metadata\Delete(),
        new \ApiPlatform\Metadata\Get(
            name: 'set_open',
            uriTemplate: '/sets/{id}/open',
            controller: SetOpenAction::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(summary: 'Opens a set and returns a list of obtained cards'),
            read: true,
            write: false,
        ),
    ]
)]
class Set
{
    #[ORM\Id]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['set:read', 'set:write', 'serie:read', 'card:read'])]
    private ?string $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['set:read', 'set:write', 'serie:read', 'card:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['set:read', 'set:write'])]
    private ?string $logo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['set:read', 'set:write'])]
    private ?\DateTime $releaseDate = null;

    #[ORM\ManyToOne(inversedBy: 'cardSets')]
    #[Groups(['set:read', 'set:write'])]
    private ?Serie $serie = null;

    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'cardSet')]
    #[Groups(['set:read'])]
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

    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static
    {
        $this->serie = $serie;

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
