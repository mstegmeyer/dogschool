<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ContractState;
use App\Enum\ContractType;
use App\Support\AppClock;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\ContractRepository::class)]
#[ORM\Table(name: 'contract')]
class Contract
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    /** Logical contract group: all versions of the same contract share this UUID. */
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $contractGroupId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Dog::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dog $dog = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $quotedMonthlyPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $registrationFee = '0.00';

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ContractType::class)]
    private ContractType $type;

    #[ORM\Column(type: Types::INTEGER)]
    private int $coursesPerWeek = 0;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ContractState::class)]
    private ContractState $state;

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
        $this->contractGroupId = Uuid::v7()->toRfc4122();
        $this->createdAt = AppClock::now();
        $this->type = ContractType::PERPETUAL;
        $this->state = ContractState::REQUESTED;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContractGroupId(): string
    {
        return $this->contractGroupId;
    }

    public function setContractGroupId(string $contractGroupId): static
    {
        $this->contractGroupId = $contractGroupId;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
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

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPrice(): string
    {
        return self::normalizeAmount($this->price);
    }

    public function setPrice(string $price): static
    {
        $this->price = self::normalizeAmount($price);

        return $this;
    }

    public function getType(): ContractType
    {
        return $this->type;
    }

    public function setType(ContractType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCoursesPerWeek(): int
    {
        return $this->coursesPerWeek;
    }

    public function setCoursesPerWeek(int $coursesPerWeek): static
    {
        $this->coursesPerWeek = $coursesPerWeek;

        return $this;
    }

    public function getState(): ContractState
    {
        return $this->state;
    }

    public function setState(ContractState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getQuotedMonthlyPrice(): string
    {
        return self::normalizeAmount($this->quotedMonthlyPrice);
    }

    public function setQuotedMonthlyPrice(string $quotedMonthlyPrice): static
    {
        $this->quotedMonthlyPrice = self::normalizeAmount($quotedMonthlyPrice);

        return $this;
    }

    public function getRegistrationFee(): string
    {
        return self::normalizeAmount($this->registrationFee);
    }

    public function setRegistrationFee(string $registrationFee): static
    {
        $this->registrationFee = self::normalizeAmount($registrationFee);

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
