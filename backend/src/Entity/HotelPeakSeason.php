<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\HotelPeakSeasonRepository::class)]
#[ORM\Table(name: 'hotel_peak_season')]
class HotelPeakSeason
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: PricingConfig::class, inversedBy: 'hotelPeakSeasons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PricingConfig $pricingConfig = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endDate = null;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPricingConfig(): ?PricingConfig
    {
        return $this->pricingConfig;
    }

    public function setPricingConfig(?PricingConfig $pricingConfig): static
    {
        $this->pricingConfig = $pricingConfig;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
}
