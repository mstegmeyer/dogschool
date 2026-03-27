<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Embeddable\Address;
use App\Entity\Embeddable\BankAccount;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\CustomerRepository::class)]
#[ORM\Table(name: 'customer')]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $calendarFeedToken;

    #[ORM\Embedded(class: Address::class)]
    private Address $address;

    #[ORM\Embedded(class: BankAccount::class)]
    private BankAccount $bankAccount;

    /** @var Collection<int, Dog> */
    #[ORM\OneToMany(targetEntity: Dog::class, mappedBy: 'customer', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dogs;

    /** @var Collection<int, Contract> */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'customer', cascade: ['persist', 'remove'])]
    private Collection $contracts;

    /** @var Collection<int, Course> */
    #[ORM\ManyToMany(targetEntity: Course::class, inversedBy: 'subscribedCustomers')]
    #[ORM\JoinTable(name: 'customer_course_subscription')]
    private Collection $subscribedCourses;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->calendarFeedToken = Uuid::v7()->toRfc4122();
        $this->address = new Address();
        $this->bankAccount = new BankAccount();
        $this->dogs = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->subscribedCourses = new ArrayCollection();
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCalendarFeedToken(): string
    {
        return $this->calendarFeedToken;
    }

    public function refreshCalendarFeedToken(): static
    {
        $this->calendarFeedToken = Uuid::v7()->toRfc4122();

        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    /** @return Collection<int, Dog> */
    public function getDogs(): Collection
    {
        return $this->dogs;
    }

    public function addDog(Dog $dog): static
    {
        if (!$this->dogs->contains($dog)) {
            $this->dogs->add($dog);
            $dog->setCustomer($this);
        }

        return $this;
    }

    public function removeDog(Dog $dog): static
    {
        if ($this->dogs->removeElement($dog)) {
            if ($dog->getCustomer() === $this) {
                $dog->setCustomer(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Contract> */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    /** @return Collection<int, Course> */
    public function getSubscribedCourses(): Collection
    {
        return $this->subscribedCourses;
    }

    public function addSubscribedCourse(Course $course): static
    {
        if (!$this->subscribedCourses->contains($course)) {
            $this->subscribedCourses->add($course);
        }

        return $this;
    }

    public function removeSubscribedCourse(Course $course): static
    {
        $this->subscribedCourses->removeElement($course);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email !== '' ? $this->email : throw new \LogicException('Customer email must not be empty.');
    }

    public function getRoles(): array
    {
        return ['ROLE_CUSTOMER'];
    }

    public function eraseCredentials(): void
    {
    }
}
