<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RecurrenceKind;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\CourseTypeRepository::class)]
#[ORM\Table(name: 'course_type')]
class CourseType
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    /** Short code e.g. JUHU, MH, AGI. */
    #[ORM\Column(type: Types::STRING, length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private string $code;

    /** Full name e.g. Junghunde, Mensch-Hund, Agility. */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    /** RECURRING = weekly slot, ONE_TIME = single session, DROP_IN = no registration. */
    #[ORM\Column(type: Types::STRING, length: 20, enumType: RecurrenceKind::class, options: ['default' => 'RECURRING'])]
    private RecurrenceKind $recurrenceKind = RecurrenceKind::RECURRING;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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

    public function getRecurrenceKind(): RecurrenceKind
    {
        return $this->recurrenceKind;
    }

    public function setRecurrenceKind(RecurrenceKind $recurrenceKind): static
    {
        $this->recurrenceKind = $recurrenceKind;

        return $this;
    }
}
