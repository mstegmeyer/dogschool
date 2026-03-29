<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\CourseType as CourseTypeEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\CourseRepository::class)]
#[ORM\Table(name: 'course')]
class Course
{
    /** Day of week 1 (Monday) through 7 (Sunday). */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 7)]
    private int $dayOfWeek;

    /** Start time as "HH:MM". */
    #[ORM\Column(type: Types::STRING, length: 5)]
    #[Assert\NotBlank]
    private string $startTime;

    /** End time as "HH:MM". */
    #[ORM\Column(type: Types::STRING, length: 5)]
    #[Assert\NotBlank]
    private string $endTime;

    /** Duration in minutes (can be calculated from start/end). */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\ManyToOne(targetEntity: CourseTypeEntity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CourseTypeEntity $courseType = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $trainer = null;

    /** Level 0-4. */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 0, max: 4)]
    private int $level = 0;

    /** Customer special wishes / comment for this course. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $archived = false;

    /** @var Collection<int, Notification> */
    #[ORM\ManyToMany(targetEntity: Notification::class, mappedBy: 'courses')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $notifications;

    /** @var Collection<int, Customer> */
    #[ORM\ManyToMany(targetEntity: Customer::class, mappedBy: 'subscribedCourses')]
    private Collection $subscribedCustomers;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->notifications = new ArrayCollection();
        $this->subscribedCustomers = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(?int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    /** Calculate duration from start/end time and store it. */
    public function computeDurationMinutes(): void
    {
        $start = \DateTimeImmutable::createFromFormat('H:i', $this->startTime);
        $end = \DateTimeImmutable::createFromFormat('H:i', $this->endTime);
        if ($start && $end) {
            $this->durationMinutes = (int) (($end->getTimestamp() - $start->getTimestamp()) / 60);
        }
    }

    public function getCourseType(): ?CourseTypeEntity
    {
        return $this->courseType;
    }

    public function setCourseType(CourseTypeEntity $courseType): static
    {
        $this->courseType = $courseType;

        return $this;
    }

    public function getTrainer(): ?User
    {
        return $this->trainer;
    }

    public function setTrainer(?User $trainer): static
    {
        $this->trainer = $trainer;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->addCourse($this);
        }

        return $this;
    }

    /** @return Collection<int, Customer> */
    public function getSubscribedCustomers(): Collection
    {
        return $this->subscribedCustomers;
    }
}
