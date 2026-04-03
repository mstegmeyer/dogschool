<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CreditTransactionType;
use App\Support\AppClock;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\CreditTransactionRepository::class)]
#[ORM\Table(name: 'credit_transaction')]
#[ORM\Index(columns: ['customer_id', 'created_at'], name: 'idx_credit_tx_customer_date')]
class CreditTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    /** Positive = granted, negative = spent. */
    #[ORM\Column(type: Types::INTEGER)]
    private int $amount;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: CreditTransactionType::class)]
    private CreditTransactionType $type;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\ManyToOne(targetEntity: CourseDate::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CourseDate $courseDate = null;

    #[ORM\ManyToOne(targetEntity: Contract::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contract $contract = null;

    /** ISO week string (e.g. "2026-W12") to prevent duplicate weekly grants. */
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $weekRef = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getType(): CreditTransactionType
    {
        return $this->type;
    }

    public function setType(CreditTransactionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCourseDate(): ?CourseDate
    {
        return $this->courseDate;
    }

    public function setCourseDate(?CourseDate $courseDate): static
    {
        $this->courseDate = $courseDate;

        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getWeekRef(): ?string
    {
        return $this->weekRef;
    }

    public function setWeekRef(?string $weekRef): static
    {
        $this->weekRef = $weekRef;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
