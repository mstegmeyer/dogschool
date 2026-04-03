<?php

declare(strict_types=1);

namespace App\Entity;

use App\Support\AppClock;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\BookingRepository::class)]
#[ORM\Table(name: 'booking')]
#[ORM\UniqueConstraint(name: 'uniq_booking_dog_coursedate', columns: ['dog_id', 'course_date_id'])]
#[ORM\Index(columns: ['customer_id'], name: 'idx_booking_customer')]
#[ORM\Index(columns: ['dog_id'], name: 'idx_booking_dog')]
class Booking
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: CourseDate::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CourseDate $courseDate = null;

    #[ORM\ManyToOne(targetEntity: Dog::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dog $dog = null;

    #[ORM\OneToOne(targetEntity: CreditTransaction::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CreditTransaction $creditTransaction = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = AppClock::now();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCourseDate(): ?CourseDate
    {
        return $this->courseDate;
    }

    public function setCourseDate(CourseDate $courseDate): static
    {
        $this->courseDate = $courseDate;

        return $this;
    }

    public function getDog(): ?Dog
    {
        return $this->dog;
    }

    public function setDog(Dog $dog): static
    {
        $this->dog = $dog;

        return $this;
    }

    public function getCreditTransaction(): ?CreditTransaction
    {
        return $this->creditTransaction;
    }

    public function setCreditTransaction(?CreditTransaction $creditTransaction): static
    {
        $this->creditTransaction = $creditTransaction;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->cancelledAt === null;
    }
}
