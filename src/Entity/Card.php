<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ApiResource]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $idAPI = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?string $setPos = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageLow = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageHigh = null;

    #[ORM\Column(enumType: Enum\CardCategory::class, nullable: true)]
    private ?Enum\CardCategory $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $illustrator = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rarity = null;

    #[ORM\Column]
    private ?bool $variantNormal = null;

    #[ORM\Column]
    private ?bool $variantReverse = null;

    #[ORM\Column]
    private ?bool $variantHolo = null;

    #[ORM\Column]
    private ?bool $variantFirstEdition = null;

    #[ORM\Column]
    private ?bool $variantPromo = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updated = null;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    private ?Set $cardSet = null;

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

    public function getSetPos(): ?string
    {
        return $this->setPos;
    }

    public function setSetPos(?string $setPos): static
    {
        $this->setPos = $setPos;

        return $this;
    }

    public function getImageLow(): ?string
    {
        return $this->imageLow;
    }

    public function setImageLow(?string $imageLow): static
    {
        $this->imageLow = $imageLow;

        return $this;
    }

    public function getImageHigh(): ?string
    {
        return $this->imageHigh;
    }

    public function setImageHigh(?string $imageHigh): static
    {
        $this->imageHigh = $imageHigh;

        return $this;
    }

    public function getCategory(): ?Enum\CardCategory
    {
        return $this->category;
    }

    public function setCategory(?Enum\CardCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getIllustrator(): ?string
    {
        return $this->illustrator;
    }

    public function setIllustrator(?string $illustrator): static
    {
        $this->illustrator = $illustrator;

        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(?string $rarity): static
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function isVariantNormal(): ?bool
    {
        return $this->variantNormal;
    }

    public function setVariantNormal(bool $variantNormal): static
    {
        $this->variantNormal = $variantNormal;

        return $this;
    }

    public function isVariantReverse(): ?bool
    {
        return $this->variantReverse;
    }

    public function setVariantReverse(bool $variantReverse): static
    {
        $this->variantReverse = $variantReverse;

        return $this;
    }

    public function isVariantHolo(): ?bool
    {
        return $this->variantHolo;
    }

    public function setVariantHolo(bool $variantHolo): static
    {
        $this->variantHolo = $variantHolo;

        return $this;
    }

    public function isVariantFirstEdition(): ?bool
    {
        return $this->variantFirstEdition;
    }

    public function setVariantFirstEdition(bool $variantFirstEdition): static
    {
        $this->variantFirstEdition = $variantFirstEdition;

        return $this;
    }

    public function isVariantPromo(): ?bool
    {
        return $this->variantPromo;
    }

    public function setVariantPromo(bool $variantPromo): static
    {
        $this->variantPromo = $variantPromo;

        return $this;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTime $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    public function getCardSet(): ?Set
    {
        return $this->cardSet;
    }

    public function setCardSet(?Set $cardSet): static
    {
        $this->cardSet = $cardSet;

        return $this;
    }
}
