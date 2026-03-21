<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ContractState;
use App\Enum\ContractType;
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ContractType::class)]
    private ContractType $type;

    #[ORM\Column(type: Types::INTEGER)]
    private int $coursesPerWeek = 0;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: ContractState::class)]
    private ContractState $state;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->contractGroupId = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
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

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
