<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\CourseDateRepository::class)]
#[ORM\Table(name: 'course_date')]
#[ORM\Index(columns: ['date'], name: 'idx_course_date_date')]
#[ORM\Index(columns: ['course_id', 'date'], name: 'idx_course_date_course_date')]
class CourseDate
{
    public const TIMEZONE = 'Europe/Berlin';

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: Types::STRING, length: 5)]
    #[Assert\NotBlank]
    private string $startTime;

    #[ORM\Column(type: Types::STRING, length: 5)]
    #[Assert\NotBlank]
    private string $endTime;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $cancelled = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'courseDate')]
    private Collection $bookings;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

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

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): static
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    /** @return list<Booking> */
    public function getActiveBookings(): array
    {
        return $this->bookings->filter(
            fn (Booking $b) => $b->getCancelledAt() === null
        )->getValues();
    }

    /** Combine the stored date + startTime into a timezone-aware instant. */
    public function startsAt(): \DateTimeImmutable
    {
        $dt = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $this->date->format('Y-m-d') . ' ' . $this->startTime,
            new \DateTimeZone(self::TIMEZONE),
        );
        if ($dt === false) {
            throw new \LogicException('Invalid course date or start time.');
        }

        return $dt;
    }

    /** True once the slot's start + 24 hours has passed. */
    public function isBookingWindowClosed(): bool
    {
        $tz = new \DateTimeZone(self::TIMEZONE);

        return new \DateTimeImmutable('now', $tz) > $this->startsAt()->modify('+24 hours');
    }
}
