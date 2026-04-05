<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\HotelBookingState;
use App\Support\AppClock;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\HotelBookingRepository::class)]
#[ORM\Table(name: 'hotel_booking')]
#[ORM\Index(columns: ['customer_id'], name: 'idx_hotel_booking_customer')]
#[ORM\Index(columns: ['dog_id'], name: 'idx_hotel_booking_dog')]
#[ORM\Index(columns: ['room_id'], name: 'idx_hotel_booking_room')]
#[ORM\Index(columns: ['state', 'start_at', 'end_at'], name: 'idx_hotel_booking_state_range')]
class HotelBooking
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'hotelBookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Dog::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dog $dog = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'hotelBookings')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Room $room = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $endAt;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: HotelBookingState::class)]
    private HotelBookingState $state;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = AppClock::now();
        $this->startAt = $this->createdAt;
        $this->endAt = $this->createdAt->modify('+1 hour');
        $this->state = HotelBookingState::REQUESTED;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDog(): ?Dog
    {
        return $this->dog;
    }

    public function setDog(?Dog $dog): static
    {
        $this->dog = $dog;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getState(): HotelBookingState
    {
        return $this->state;
    }

    public function setState(HotelBookingState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
