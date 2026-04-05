<?php

declare(strict_types=1);

namespace App\Entity;

use App\Support\AppClock;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\RoomRepository::class)]
#[ORM\Table(name: 'room')]
#[ORM\UniqueConstraint(name: 'uniq_room_name', columns: ['name'])]
class Room
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    private int $squareMeters = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, HotelBooking> */
    #[ORM\OneToMany(targetEntity: HotelBooking::class, mappedBy: 'room')]
    private Collection $hotelBookings;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = AppClock::now();
        $this->hotelBookings = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSquareMeters(): int
    {
        return $this->squareMeters;
    }

    public function setSquareMeters(int $squareMeters): static
    {
        $this->squareMeters = $squareMeters;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, HotelBooking> */
    public function getHotelBookings(): Collection
    {
        return $this->hotelBookings;
    }
}
