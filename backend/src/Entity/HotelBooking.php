<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\HotelBookingPricingKind;
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

    #[ORM\Column(type: Types::STRING, length: 20, enumType: HotelBookingPricingKind::class)]
    private HotelBookingPricingKind $pricingKind;

    #[ORM\Column(type: Types::INTEGER)]
    private int $billableDays = 1;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $includesTravelProtection = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $quotedTotalPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $serviceFee = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $travelProtectionPrice = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerComment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminComment = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $pricingSnapshot = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = AppClock::now();
        $this->startAt = $this->createdAt;
        $this->endAt = $this->createdAt->modify('+1 hour');
        $this->state = HotelBookingState::REQUESTED;
        $this->pricingKind = HotelBookingPricingKind::DAYCARE;
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

    public function getPricingKind(): HotelBookingPricingKind
    {
        return $this->pricingKind;
    }

    public function setPricingKind(HotelBookingPricingKind $pricingKind): static
    {
        $this->pricingKind = $pricingKind;

        return $this;
    }

    public function getBillableDays(): int
    {
        return $this->billableDays;
    }

    public function setBillableDays(int $billableDays): static
    {
        $this->billableDays = $billableDays;

        return $this;
    }

    public function includesTravelProtection(): bool
    {
        return $this->includesTravelProtection;
    }

    public function setIncludesTravelProtection(bool $includesTravelProtection): static
    {
        $this->includesTravelProtection = $includesTravelProtection;

        return $this;
    }

    public function getTotalPrice(): string
    {
        return self::normalizeAmount($this->totalPrice);
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = self::normalizeAmount($totalPrice);

        return $this;
    }

    public function getQuotedTotalPrice(): string
    {
        return self::normalizeAmount($this->quotedTotalPrice);
    }

    public function setQuotedTotalPrice(string $quotedTotalPrice): static
    {
        $this->quotedTotalPrice = self::normalizeAmount($quotedTotalPrice);

        return $this;
    }

    public function getServiceFee(): string
    {
        return self::normalizeAmount($this->serviceFee);
    }

    public function setServiceFee(string $serviceFee): static
    {
        $this->serviceFee = self::normalizeAmount($serviceFee);

        return $this;
    }

    public function getTravelProtectionPrice(): string
    {
        return self::normalizeAmount($this->travelProtectionPrice);
    }

    public function setTravelProtectionPrice(string $travelProtectionPrice): static
    {
        $this->travelProtectionPrice = self::normalizeAmount($travelProtectionPrice);

        return $this;
    }

    public function getCustomerComment(): ?string
    {
        return $this->customerComment;
    }

    public function setCustomerComment(?string $customerComment): static
    {
        $this->customerComment = $customerComment;

        return $this;
    }

    public function getAdminComment(): ?string
    {
        return $this->adminComment;
    }

    public function setAdminComment(?string $adminComment): static
    {
        $this->adminComment = $adminComment;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPricingSnapshot(): array
    {
        return $this->pricingSnapshot;
    }

    /**
     * @param array<string, mixed> $pricingSnapshot
     */
    public function setPricingSnapshot(array $pricingSnapshot): static
    {
        $this->pricingSnapshot = $pricingSnapshot;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function normalizeAmount(string $amount): string
    {
        return number_format((float) str_replace(',', '.', trim($amount)), 2, '.', '');
    }
}
