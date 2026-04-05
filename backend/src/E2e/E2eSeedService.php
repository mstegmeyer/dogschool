<?php

declare(strict_types=1);

namespace App\E2e;

use App\Entity\Booking;
use App\Entity\Contract;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\HotelBooking;
use App\Entity\HotelPeakSeason;
use App\Entity\Notification;
use App\Entity\PricingConfig;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\ContractState;
use App\Enum\ContractType;
use App\Enum\CreditTransactionType;
use App\Enum\HotelBookingPricingKind;
use App\Enum\HotelBookingState;
use App\Repository\CourseTypeRepository;
use App\Repository\UserRepository;
use App\Service\PricingConfigProvider;
use App\Service\PricingEngine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @phpstan-type ManifestCustomer array{
 *     id: string,
 *     name: string,
 *     email: string,
 *     password: string,
 *     calendarFeedToken: string,
 *     dogIds: list<string>,
 *     dogNames: list<string>,
 *     dogShoulderHeights: list<int>
 * }
 * @phpstan-type ManifestCourseType array{id: string, code: string, name: string}
 * @phpstan-type ManifestCourseDate array{current: string, next: string}
 * @phpstan-type ManifestRoom array{id: string, name: string, squareMeters: int}
 */
final class E2eSeedService
{
    public const FIXED_NOW = '2026-04-06T09:00:00+02:00';
    public const CUSTOMER_PASSWORD = 'playwright-pass';
    public const ADMIN_PASSWORD = 'change-me';

    private int $customerSequence = 0;
    private int $contractSequence = 0;
    private int $notificationSequence = 0;
    private int $creditSequence = 0;
    private int $bookingSequence = 0;
    private int $courseDateSequence = 0;
    private int $hotelRoomSequence = 0;
    private int $hotelBookingSequence = 0;

    private const PERSONAS = [
        'customer_empty' => [
            'name' => 'Empty E2E',
            'email' => 'empty.e2e@example.com',
            'dogs' => [],
        ],
        'customer_single_dog' => [
            'name' => 'Single E2E',
            'email' => 'single.e2e@example.com',
            'dogs' => [
                ['name' => 'Rex', 'race' => 'Golden Retriever', 'gender' => 'male', 'color' => 'Golden', 'shoulderHeightCm' => 57],
            ],
        ],
        'customer_multi_dog' => [
            'name' => 'Multi E2E',
            'email' => 'multi.e2e@example.com',
            'dogs' => [
                ['name' => 'Luna', 'race' => 'Labrador', 'gender' => 'female', 'color' => 'Schwarz', 'shoulderHeightCm' => 54],
                ['name' => 'Balu', 'race' => 'Australian Shepherd', 'gender' => 'male', 'color' => 'Blue Merle', 'shoulderHeightCm' => 56],
            ],
        ],
        'customer_dashboard' => [
            'name' => 'Dashboard E2E',
            'email' => 'dashboard.e2e@example.com',
            'dogs' => [
                ['name' => 'Luna', 'race' => 'Labrador', 'gender' => 'female', 'color' => 'Schwarz', 'shoulderHeightCm' => 48],
                ['name' => 'Balu', 'race' => 'Australian Shepherd', 'gender' => 'male', 'color' => 'Blue Merle', 'shoulderHeightCm' => 55],
            ],
        ],
        'customer_calendar_multi' => [
            'name' => 'Calendar Multi E2E',
            'email' => 'calendar.multi.e2e@example.com',
            'dogs' => [
                ['name' => 'Balu', 'race' => 'Australian Shepherd', 'gender' => 'male', 'color' => 'Blue Merle', 'shoulderHeightCm' => 56],
                ['name' => 'Nala', 'race' => 'Labrador', 'gender' => 'female', 'color' => 'Gelb', 'shoulderHeightCm' => 51],
            ],
        ],
        'customer_calendar_booked' => [
            'name' => 'Calendar Booked E2E',
            'email' => 'calendar.booked.e2e@example.com',
            'dogs' => [
                ['name' => 'Balu', 'race' => 'Australian Shepherd', 'gender' => 'male', 'color' => 'Blue Merle', 'shoulderHeightCm' => 56],
            ],
        ],
        'customer_profile' => [
            'name' => 'Profile E2E',
            'email' => 'profile.e2e@example.com',
            'dogs' => [
                ['name' => 'Milo', 'race' => 'Border Collie', 'gender' => 'male', 'color' => 'Schwarz-Weiss', 'shoulderHeightCm' => 50],
            ],
        ],
        'customer_contracts' => [
            'name' => 'Contracts E2E',
            'email' => 'contracts.e2e@example.com',
            'dogs' => [
                ['name' => 'Kira', 'race' => 'Vizsla', 'gender' => 'female', 'color' => 'Braun', 'shoulderHeightCm' => 55],
            ],
        ],
        'customer_hotel_booking' => [
            'name' => 'Hotel Booking E2E',
            'email' => 'hotel.booking.e2e@example.com',
            'dogs' => [
                ['name' => 'Momo', 'race' => 'Labradoodle', 'gender' => 'female', 'color' => 'Apricot', 'shoulderHeightCm' => 58],
            ],
        ],
        'customer_contract_pending' => [
            'name' => 'Pending Contract E2E',
            'email' => 'pending.contract.e2e@example.com',
            'dogs' => [
                ['name' => 'Maja', 'race' => 'Mischling', 'gender' => 'female', 'color' => 'Braun', 'shoulderHeightCm' => 46],
            ],
        ],
        'customer_contract_approve' => [
            'name' => 'Approve Contract E2E',
            'email' => 'approve.contract.e2e@example.com',
            'dogs' => [
                ['name' => 'Apollo', 'race' => 'Weimaraner', 'gender' => 'male', 'color' => 'Silber', 'shoulderHeightCm' => 63],
            ],
        ],
        'customer_contract_decline' => [
            'name' => 'Decline Contract E2E',
            'email' => 'decline.contract.e2e@example.com',
            'dogs' => [
                ['name' => 'Nelly', 'race' => 'Beagle', 'gender' => 'female', 'color' => 'Tricolor', 'shoulderHeightCm' => 38],
            ],
        ],
        'customer_contract_cancel' => [
            'name' => 'Cancel Contract E2E',
            'email' => 'cancel.contract.e2e@example.com',
            'dogs' => [
                ['name' => 'Otis', 'race' => 'Boxer', 'gender' => 'male', 'color' => 'Rotbraun', 'shoulderHeightCm' => 60],
            ],
        ],
        'customer_archive_booking' => [
            'name' => 'Archive Booking E2E',
            'email' => 'archive.booking.e2e@example.com',
            'dogs' => [
                ['name' => 'Pepper', 'race' => 'Pudel', 'gender' => 'female', 'color' => 'Weiss', 'shoulderHeightCm' => 42],
            ],
        ],
        'customer_calendar_cancel' => [
            'name' => 'Calendar Cancel E2E',
            'email' => 'calendar.cancel.e2e@example.com',
            'dogs' => [
                ['name' => 'Frieda', 'race' => 'Whippet', 'gender' => 'female', 'color' => 'Gestromt', 'shoulderHeightCm' => 48],
            ],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CourseTypeRepository $courseTypeRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function seed(): array
    {
        $manifest = [
            'fixedNow' => self::FIXED_NOW,
            'week' => [
                'monday' => $this->referenceMonday()->format('Y-m-d'),
                'nextMonday' => $this->referenceMonday()->modify('+1 week')->format('Y-m-d'),
            ],
            'admin' => [
                'username' => 'florian',
                'password' => self::ADMIN_PASSWORD,
            ],
            'customers' => [],
            'trainers' => [],
            'courseTypes' => [],
            'courses' => [],
            'courseDates' => [],
            'contracts' => [],
            'notifications' => [],
            'hotelRooms' => [],
            'hotelBookings' => [],
        ];

        $this->createPricingConfig();
        $courseTypes = $this->indexCourseTypes();
        $trainers = $this->indexTrainers();

        foreach ($trainers as $username => $trainer) {
            $manifest['trainers'][$username] = [
                'id' => $trainer->getId(),
                'username' => $trainer->getUsername(),
                'fullName' => $trainer->getFullName(),
            ];
        }

        $this->createDedicatedCourseTypes($manifest);

        $customers = $this->createCustomers($manifest);
        $this->createFillerCustomers($manifest, $customers);

        $courses = $this->createCourses($courseTypes, $trainers, $manifest);
        $courseDates = $this->createCourseDates($courses, $manifest);

        $this->assignSubscriptions($customers, $courses);
        $contracts = $this->createContracts($customers, $manifest);
        $this->createCreditsAndBookings($customers, $courses, $courseDates, $contracts);
        $this->createNotifications($courses, $trainers, $manifest);
        $this->createHotelData($customers, $manifest);

        $this->em->flush();

        return $manifest;
    }

    /**
     * @return array<string, CourseType>
     */
    private function indexCourseTypes(): array
    {
        $indexed = [];
        foreach ($this->courseTypeRepository->findAll() as $courseType) {
            $indexed[$courseType->getCode()] = $courseType;
        }

        return $indexed;
    }

    /**
     * @return array<string, User>
     */
    private function indexTrainers(): array
    {
        $indexed = [];
        foreach ($this->userRepository->findAll() as $user) {
            $indexed[$user->getUsername()] = $user;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function createDedicatedCourseTypes(array &$manifest): void
    {
        /** @var array<string, ManifestCourseType> $manifestCourseTypes */
        $manifestCourseTypes = &$manifest['courseTypes'];

        foreach ([
            'course_type_edit' => ['code' => 'E2EEDIT', 'name' => 'E2E Editierbar'],
            'course_type_delete' => ['code' => 'E2EDEL', 'name' => 'E2E Loeschbar'],
        ] as $key => $definition) {
            $courseType = new CourseType();
            $courseType->setCode($definition['code']);
            $courseType->setName($definition['name']);
            $this->em->persist($courseType);
            $manifestCourseTypes[$key] = [
                'id' => $courseType->getId(),
                'code' => $courseType->getCode(),
                'name' => $courseType->getName(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $manifest
     *
     * @return array<string, array{customer: Customer, dogs: array<int, Dog>}>
     */
    private function createCustomers(array &$manifest): array
    {
        $customers = [];
        /** @var array<string, ManifestCustomer> $manifestCustomers */
        $manifestCustomers = &$manifest['customers'];

        foreach (self::PERSONAS as $key => $definition) {
            $address = null;
            $bank = null;
            if ($key === 'customer_profile') {
                $address = ['street' => 'Hauptstrasse 12', 'postalCode' => '48143', 'city' => 'Muenster'];
                $bank = ['iban' => 'DE89370400440532013000', 'bic' => 'COBADEFFXXX', 'accountHolder' => 'Profile E2E'];
            }

            $customers[$key] = $this->createCustomerRecord(
                $definition['name'],
                $definition['email'],
                $definition['dogs'],
                $manifestCustomers,
                $key,
                $address,
                $bank,
            );
        }

        return $customers;
    }

    /**
     * @param array<string, mixed>                                            $manifest
     * @param array<string, array{customer: Customer, dogs: array<int, Dog>}> $customers
     */
    private function createFillerCustomers(array &$manifest, array &$customers): void
    {
        /** @var array<string, ManifestCustomer> $manifestCustomers */
        $manifestCustomers = &$manifest['customers'];

        for ($index = 1; $index <= 15; ++$index) {
            $key = sprintf('customer_fill_%02d', $index);
            $customers[$key] = $this->createCustomerRecord(
                sprintf('Seed Customer %02d', $index),
                sprintf('seed.customer.%02d@example.com', $index),
                [[
                    'name' => sprintf('SeedDog%02d', $index),
                    'race' => 'Mischling',
                    'gender' => $index % 2 === 0 ? 'female' : 'male',
                    'color' => $index % 2 === 0 ? 'Braun' : 'Schwarz',
                    'shoulderHeightCm' => 40 + (($index % 7) * 4),
                ]],
                $manifestCustomers,
                $key,
            );
        }
    }

    /**
     * @param array<int, array{name: string, race: string, gender: string, color: string, shoulderHeightCm?: int}> $dogDefinitions
     * @param array<string, ManifestCustomer>                                                                      $manifestCustomers
     * @param array{street: string, postalCode: string, city: string}|null                                         $address
     * @param array{iban: string, bic: string, accountHolder: string}|null                                         $bank
     *
     * @return array{customer: Customer, dogs: array<int, Dog>}
     */
    private function createCustomerRecord(
        string $name,
        string $email,
        array $dogDefinitions,
        array &$manifestCustomers,
        string $key,
        ?array $address = null,
        ?array $bank = null,
    ): array {
        $customer = new Customer();
        $customer->setName($name);
        $customer->setEmail($email);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, self::CUSTOMER_PASSWORD));
        $customer->setCalendarFeedToken($this->deterministicUuid(sprintf('calendar-feed:%s', $key)));

        if ($address !== null) {
            $customer->getAddress()->setStreet($address['street']);
            $customer->getAddress()->setPostalCode($address['postalCode']);
            $customer->getAddress()->setCity($address['city']);
            $customer->getAddress()->setCountry('DE');
        }

        if ($bank !== null) {
            $customer->getBankAccount()->setIban($bank['iban']);
            $customer->getBankAccount()->setBic($bank['bic']);
            $customer->getBankAccount()->setAccountHolder($bank['accountHolder']);
        }

        $dogs = [];
        foreach ($dogDefinitions as $definition) {
            $dog = new Dog();
            $dog->setName($definition['name']);
            $dog->setRace($definition['race']);
            $dog->setGender($definition['gender']);
            $dog->setColor($definition['color']);
            $dog->setShoulderHeightCm((int) ($definition['shoulderHeightCm'] ?? 48));
            $customer->addDog($dog);
            $this->em->persist($dog);
            $dogs[] = $dog;
        }

        $this->em->persist($customer);
        $this->stampCreatedAt($customer, $this->sequenceTime($this->customerSequence));

        $manifestCustomers[$key] = [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'email' => $customer->getEmail(),
            'password' => self::CUSTOMER_PASSWORD,
            'calendarFeedToken' => $customer->getCalendarFeedToken(),
            'dogIds' => array_map(static fn (Dog $dog): string => $dog->getId(), $dogs),
            'dogNames' => array_map(static fn (Dog $dog): string => $dog->getName(), $dogs),
            'dogShoulderHeights' => array_map(static fn (Dog $dog): int => $dog->getShoulderHeightCm(), $dogs),
        ];

        return ['customer' => $customer, 'dogs' => $dogs];
    }

    private function deterministicUuid(string $seed): string
    {
        $hash = md5($seed);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }

    /**
     * @param array<string, CourseType> $courseTypes
     * @param array<string, User>       $trainers
     * @param array<string, mixed>      $manifest
     *
     * @return array<string, Course>
     */
    private function createCourses(array $courseTypes, array $trainers, array &$manifest): array
    {
        /** @var array<string, string> $manifestCourses */
        $manifestCourses = &$manifest['courses'];

        $definitions = [
            ['key' => 'customer_single_course', 'code' => 'MH', 'day' => 1, 'start' => '10:00', 'end' => '11:00', 'level' => 1, 'trainer' => 'florian', 'comment' => 'Single booking flow'],
            ['key' => 'customer_multi_course', 'code' => 'APP', 'day' => 1, 'start' => '12:00', 'end' => '13:00', 'level' => 2, 'trainer' => 'manuela', 'comment' => 'Multi booking flow'],
            ['key' => 'customer_booked_course', 'code' => 'TK', 'day' => 2, 'start' => '11:00', 'end' => '12:00', 'level' => 1, 'trainer' => 'caro', 'comment' => 'Booked course'],
            ['key' => 'customer_detail_course', 'code' => 'AGI', 'day' => 3, 'start' => '18:00', 'end' => '19:00', 'level' => 3, 'trainer' => 'manuela', 'comment' => 'Detail modal'],
            ['key' => 'customer_profile_course', 'code' => 'MT', 'day' => 4, 'start' => '09:00', 'end' => '10:00', 'level' => 1, 'trainer' => 'lea', 'comment' => 'Profile overview'],
            ['key' => 'customer_contracts_course', 'code' => 'JUHU', 'day' => 5, 'start' => '10:00', 'end' => '11:00', 'level' => 0, 'trainer' => 'florian', 'comment' => 'Contracts page'],
            ['key' => 'admin_edit_course', 'code' => 'CC', 'day' => 2, 'start' => '14:00', 'end' => '15:00', 'level' => 1, 'trainer' => 'florian', 'comment' => 'Edit me'],
            ['key' => 'admin_archive_course', 'code' => 'RO', 'day' => 3, 'start' => '16:00', 'end' => '17:00', 'level' => 2, 'trainer' => 'manuela', 'comment' => 'Archive me'],
            ['key' => 'admin_unarchive_course', 'code' => 'DF', 'day' => 4, 'start' => '17:00', 'end' => '18:00', 'level' => 1, 'trainer' => 'caro', 'comment' => 'Unarchive me', 'archived' => true],
            ['key' => 'admin_trainer_override_course', 'code' => 'THS', 'day' => 1, 'start' => '15:00', 'end' => '16:00', 'level' => 1, 'trainer' => 'florian', 'comment' => 'Trainer override'],
            ['key' => 'admin_cancel_course', 'code' => 'DIA', 'day' => 1, 'start' => '16:00', 'end' => '17:00', 'level' => 2, 'trainer' => 'lea', 'comment' => 'Cancel me'],
            ['key' => 'admin_reactivate_course', 'code' => 'FSTS', 'day' => 1, 'start' => '17:00', 'end' => '18:00', 'level' => 2, 'trainer' => 'caro', 'comment' => 'Reactivate me'],
            ['key' => 'customer_dashboard_course', 'code' => 'DS', 'day' => 2, 'start' => '13:00', 'end' => '14:00', 'level' => 1, 'trainer' => 'lea', 'comment' => 'Dashboard quick booking'],
        ];

        $fillerCodes = ['AGI', 'APP', 'CC', 'DF', 'DIA', 'FSTS', 'JUHU', 'MH', 'MT', 'RO', 'THS', 'TK'];
        $trainerRotation = ['florian', 'manuela', 'caro', 'lea'];
        for ($index = 0; $index < 10; ++$index) {
            $definitions[] = [
                'key' => sprintf('filler_course_%02d', $index + 1),
                'code' => $fillerCodes[$index % count($fillerCodes)],
                'day' => ($index % 5) + 1,
                'start' => sprintf('%02d:00', 8 + $index),
                'end' => sprintf('%02d:00', 9 + $index),
                'level' => $index % 4,
                'trainer' => $trainerRotation[$index % count($trainerRotation)],
                'comment' => sprintf('Filler course %02d', $index + 1),
            ];
        }

        $courses = [];
        foreach ($definitions as $definition) {
            $courseType = $courseTypes[$definition['code']] ?? null;
            $trainer = $trainers[$definition['trainer']] ?? null;
            if ($courseType === null || $trainer === null) {
                continue;
            }

            $course = new Course();
            $course->setCourseType($courseType);
            $course->setDayOfWeek($definition['day']);
            $course->setStartTime($definition['start']);
            $course->setEndTime($definition['end']);
            $course->setLevel($definition['level']);
            $course->setTrainer($trainer);
            $course->setComment($definition['comment']);
            $course->setArchived(($definition['archived'] ?? false) === true);
            $course->computeDurationMinutes();
            $this->em->persist($course);

            $courses[$definition['key']] = $course;
            $manifestCourses[$definition['key']] = $course->getId();
        }

        return $courses;
    }

    /**
     * @param array<string, Course> $courses
     * @param array<string, mixed>  $manifest
     *
     * @return array<string, array{current: CourseDate, next: CourseDate}>
     */
    private function createCourseDates(array $courses, array &$manifest): array
    {
        $courseDates = [];
        $cancelledCurrentKeys = ['admin_reactivate_course'];
        /** @var array<string, ManifestCourseDate> $manifestCourseDates */
        $manifestCourseDates = &$manifest['courseDates'];

        foreach ($courses as $key => $course) {
            $current = $this->createCourseDateForWeek(
                $course,
                $this->weekDateForCourse($course, 0),
                in_array($key, $cancelledCurrentKeys, true),
            );
            $next = $this->createCourseDateForWeek(
                $course,
                $this->weekDateForCourse($course, 1),
            );

            $courseDates[$key] = ['current' => $current, 'next' => $next];
            $manifestCourseDates[$key] = [
                'current' => $current->getId(),
                'next' => $next->getId(),
            ];
        }

        return $courseDates;
    }

    private function createCourseDateForWeek(Course $course, \DateTimeImmutable $date, bool $cancelled = false): CourseDate
    {
        $courseDate = new CourseDate();
        $courseDate->setCourse($course);
        $courseDate->setTrainer($course->getTrainer());
        $courseDate->setDate($date);
        $courseDate->setStartTime($course->getStartTime());
        $courseDate->setEndTime($course->getEndTime());
        $courseDate->setCancelled($cancelled);
        $this->em->persist($courseDate);
        $this->stampCreatedAt($courseDate, $this->sequenceTime($this->courseDateSequence, -5));

        return $courseDate;
    }

    private function referenceNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::FIXED_NOW);
    }

    private function referenceMonday(): \DateTimeImmutable
    {
        return $this->referenceNow()->modify('monday this week')->setTime(0, 0, 0);
    }

    private function weekDateForCourse(Course $course, int $weekOffset): \DateTimeImmutable
    {
        return $this->referenceMonday()
            ->modify(sprintf('+%d days', $course->getDayOfWeek() - 1))
            ->modify(sprintf('+%d week', $weekOffset));
    }

    /**
     * @param array<string, array{customer: Customer, dogs: array<int, Dog>}> $customers
     * @param array<string, Course>                                           $courses
     */
    private function assignSubscriptions(array $customers, array $courses): void
    {
        foreach ([
            'customer_single_dog' => ['customer_single_course'],
            'customer_multi_dog' => ['customer_multi_course', 'customer_booked_course', 'customer_detail_course', 'customer_dashboard_course'],
            'customer_dashboard' => ['customer_dashboard_course'],
            'customer_calendar_multi' => ['customer_multi_course'],
            'customer_calendar_booked' => ['customer_booked_course'],
            'customer_profile' => ['customer_profile_course'],
            'customer_contracts' => ['customer_contracts_course'],
            'customer_archive_booking' => ['admin_archive_course'],
            'customer_calendar_cancel' => ['admin_cancel_course'],
        ] as $customerKey => $courseKeys) {
            foreach ($courseKeys as $courseKey) {
                $customers[$customerKey]['customer']->addSubscribedCourse($courses[$courseKey]);
            }
        }

        foreach (['customer_fill_01', 'customer_fill_02', 'customer_fill_03'] as $customerKey) {
            $customers[$customerKey]['customer']->addSubscribedCourse($courses['admin_archive_course']);
        }
    }

    /**
     * @param array<string, array{customer: Customer, dogs: array<int, Dog>}> $customers
     * @param array<string, mixed>                                            $manifest
     *
     * @return array<string, Contract>
     */
    private function createContracts(array $customers, array &$manifest): array
    {
        $contracts = [];
        $states = [
            'customer_contract_pending' => ContractState::PENDING_CUSTOMER_APPROVAL,
            'customer_contract_approve' => ContractState::REQUESTED,
            'customer_contract_decline' => ContractState::REQUESTED,
            'customer_contract_cancel' => ContractState::ACTIVE,
            'customer_fill_14' => ContractState::DECLINED,
            'customer_fill_15' => ContractState::CANCELLED,
        ];

        foreach ($customers as $key => $entry) {
            if ($entry['dogs'] === []) {
                continue;
            }

            $state = $states[$key] ?? ContractState::ACTIVE;
            $coursesPerWeek = match ($key) {
                'customer_profile' => 2,
                'customer_multi_dog', 'customer_contract_cancel' => 3,
                default => 1,
            };

            $contract = new Contract();
            $contract->setCustomer($entry['customer']);
            $contract->setDog($entry['dogs'][0]);
            $contract->setCoursesPerWeek($coursesPerWeek);
            $contract->setType(ContractType::PERPETUAL);
            $contract->setState($state);
            $contract->setStartDate($this->referenceMonday()->modify('-2 months'));
            $contract->setEndDate($state === ContractState::CANCELLED ? $this->referenceMonday()->modify('+2 months')->modify('last day of this month') : null);
            $contract->setCustomerComment('Bitte bei Bedarf auf individuelle Trainingswuensche achten.');
            $this->applyContractPricing($contract, $state === ContractState::PENDING_CUSTOMER_APPROVAL ? 24_00 : 0, true);
            if ($state === ContractState::PENDING_CUSTOMER_APPROVAL) {
                $contract->setAdminComment('Preis angepasst wegen zusaetzlicher Einzelbetreuung.');
            }

            $this->em->persist($contract);
            $this->stampCreatedAt($contract, $this->sequenceTime($this->contractSequence, -10));
            $contracts[$key] = $contract;
        }

        $manifest['contracts'] = [
            'dashboard' => $contracts['customer_contract_pending']->getId(),
            'approve' => $contracts['customer_contract_approve']->getId(),
            'decline' => $contracts['customer_contract_decline']->getId(),
            'cancel' => $contracts['customer_contract_cancel']->getId(),
            'customerProfile' => $contracts['customer_profile']->getId(),
            'customerContracts' => $contracts['customer_contracts']->getId(),
            'pendingCustomerReview' => $contracts['customer_contract_pending']->getId(),
        ];

        return $contracts;
    }

    /**
     * @param array<string, array{customer: Customer, dogs: array<int, Dog>}> $customers
     * @param array<string, Course>                                           $courses
     * @param array<string, array{current: CourseDate, next: CourseDate}>     $courseDates
     * @param array<string, Contract>                                         $contracts
     */
    private function createCreditsAndBookings(array $customers, array $courses, array $courseDates, array $contracts): void
    {
        $currentWeek = $this->referenceNow()->format('o-\WW');
        $previousWeek = $this->referenceNow()->modify('-1 week')->format('o-\WW');

        $this->createWeeklyGrant($customers['customer_single_dog']['customer'], $contracts['customer_single_dog'], 3, $currentWeek, 'Single E2E current week');
        $this->createWeeklyGrant($customers['customer_multi_dog']['customer'], $contracts['customer_multi_dog'], 4, $currentWeek, 'Multi E2E current week');
        $this->createWeeklyGrant($customers['customer_multi_dog']['customer'], $contracts['customer_multi_dog'], 2, $previousWeek, 'Multi E2E previous week');
        $this->createWeeklyGrant($customers['customer_dashboard']['customer'], $contracts['customer_dashboard'], 3, $currentWeek, 'Dashboard E2E current week');
        $this->createWeeklyGrant($customers['customer_calendar_multi']['customer'], $contracts['customer_calendar_multi'], 3, $currentWeek, 'Calendar Multi E2E current week');
        $this->createWeeklyGrant($customers['customer_calendar_booked']['customer'], $contracts['customer_calendar_booked'], 2, $currentWeek, 'Calendar Booked E2E current week');
        $this->createWeeklyGrant($customers['customer_profile']['customer'], $contracts['customer_profile'], 2, $previousWeek, 'Profile E2E previous week');
        $this->createManualAdjustment($customers['customer_profile']['customer'], 1, 'Profil-Bonus');
        $this->createWeeklyGrant($customers['customer_contracts']['customer'], $contracts['customer_contracts'], 2, $currentWeek, 'Contracts E2E current week');
        $this->createWeeklyGrant($customers['customer_archive_booking']['customer'], $contracts['customer_archive_booking'], 2, $currentWeek, 'Archive Booking current week');
        $this->createWeeklyGrant($customers['customer_calendar_cancel']['customer'], $contracts['customer_calendar_cancel'], 2, $currentWeek, 'Calendar Cancel current week');

        $this->createBooking(
            $customers['customer_calendar_booked']['customer'],
            $customers['customer_calendar_booked']['dogs'][0],
            $courseDates['customer_booked_course']['current'],
            'Vorhandene Buchung',
        );

        $this->createBooking(
            $customers['customer_archive_booking']['customer'],
            $customers['customer_archive_booking']['dogs'][0],
            $courseDates['admin_archive_course']['next'],
            'Archivierungs-Test',
        );

        $this->createBooking(
            $customers['customer_calendar_cancel']['customer'],
            $customers['customer_calendar_cancel']['dogs'][0],
            $courseDates['admin_cancel_course']['current'],
            'Kalender-Absage-Test',
        );
    }

    private function createWeeklyGrant(Customer $customer, Contract $contract, int $amount, string $weekRef, string $label): void
    {
        $transaction = new CreditTransaction();
        $transaction->setCustomer($customer);
        $transaction->setContract($contract);
        $transaction->setAmount($amount);
        $transaction->setType(CreditTransactionType::WEEKLY_GRANT);
        $transaction->setWeekRef($weekRef);
        $transaction->setDescription(sprintf('%s (%s)', $label, $weekRef));
        $this->em->persist($transaction);
        $this->stampCreatedAt($transaction, $this->sequenceTime($this->creditSequence, -20));
    }

    private function createManualAdjustment(Customer $customer, int $amount, string $description): void
    {
        $transaction = new CreditTransaction();
        $transaction->setCustomer($customer);
        $transaction->setAmount($amount);
        $transaction->setType(CreditTransactionType::MANUAL_ADJUSTMENT);
        $transaction->setDescription($description);
        $this->em->persist($transaction);
        $this->stampCreatedAt($transaction, $this->sequenceTime($this->creditSequence, -20));
    }

    private function createBooking(Customer $customer, Dog $dog, CourseDate $courseDate, string $label): void
    {
        $transaction = new CreditTransaction();
        $transaction->setCustomer($customer);
        $transaction->setCourseDate($courseDate);
        $transaction->setAmount(-1);
        $transaction->setType(CreditTransactionType::BOOKING);
        $transaction->setDescription(sprintf('%s (%s)', $label, $dog->getName()));
        $this->em->persist($transaction);
        $this->stampCreatedAt($transaction, $this->sequenceTime($this->creditSequence, -20));

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setDog($dog);
        $booking->setCourseDate($courseDate);
        $booking->setCreditTransaction($transaction);
        $this->em->persist($booking);
        $this->stampCreatedAt($booking, $this->sequenceTime($this->bookingSequence, -15));
    }

    /**
     * @param array<string, Course> $courses
     * @param array<string, User>   $trainers
     * @param array<string, mixed>  $manifest
     */
    private function createNotifications(array $courses, array $trainers, array &$manifest): void
    {
        $create = function (string $title, string $message, User $author, array $courseKeys = [], ?\DateTimeImmutable $pinnedUntil = null) use ($courses): Notification {
            $notification = new Notification();
            $notification->setTitle($title);
            $notification->setMessage($message);
            $notification->setAuthor($author);
            $notification->setPinnedUntil($pinnedUntil);

            foreach ($courseKeys as $courseKey) {
                $notification->addCourse($courses[$courseKey]);
            }

            $this->em->persist($notification);
            $this->stampCreatedAt($notification, $this->sequenceTime($this->notificationSequence, -30));

            return $notification;
        };

        $pinnedGlobal = $create(
            'Wichtiger Wochenhinweis',
            'Diese Mitteilung ist angepinnt und bleibt fuer die Visuals stabil sichtbar.',
            $trainers['florian'],
            [],
            $this->referenceNow()->modify('+14 days')->setTime(23, 59, 59),
        );

        $courseNotification = $create(
            'Apportieren verlegt',
            'Bitte bringt wetterfeste Kleidung mit. Der Treffpunkt wurde angepasst.',
            $trainers['manuela'],
            ['customer_multi_course'],
        );

        $editNotification = $create(
            'Bearbeitbare Mitteilung',
            'Diese Mitteilung wird im Admin-Bereich editiert.',
            $trainers['caro'],
            ['customer_detail_course'],
        );

        $deleteNotification = $create(
            'Loeschbare Mitteilung',
            'Diese Mitteilung wird im Admin-Bereich geloescht.',
            $trainers['lea'],
        );

        $authorRotation = ['florian', 'manuela', 'caro', 'lea'];
        for ($index = 1; $index <= 18; ++$index) {
            $author = $trainers[$authorRotation[$index % count($authorRotation)]];
            $courseKeys = $index % 3 === 0 ? [] : [sprintf('filler_course_%02d', (($index - 1) % 10) + 1)];
            $pinnedUntil = $index <= 2
                ? $this->referenceNow()->modify(sprintf('+%d days', 7 + $index))->setTime(23, 59, 59)
                : null;

            $create(
                sprintf('Seed Mitteilung %02d', $index),
                sprintf('Deterministische Inhaltsspur fuer Mitteilung %02d.', $index),
                $author,
                $courseKeys,
                $pinnedUntil,
            );
        }

        $manifest['notifications'] = [
            'pinnedGlobal' => $pinnedGlobal->getId(),
            'courseScoped' => $courseNotification->getId(),
            'edit' => $editNotification->getId(),
            'delete' => $deleteNotification->getId(),
        ];
    }

    /**
     * @param array<string, array{customer: Customer, dogs: array<int, Dog>}> $customers
     * @param array<string, mixed>                                            $manifest
     */
    private function createHotelData(array $customers, array &$manifest): void
    {
        /** @var array<string, ManifestRoom> $manifestRooms */
        $manifestRooms = &$manifest['hotelRooms'];
        /** @var array<string, string> $manifestHotelBookings */
        $manifestHotelBookings = &$manifest['hotelBookings'];

        $rooms = [];
        foreach ([
            'small' => ['name' => 'Waldzimmer', 'squareMeters' => 10],
            'medium' => ['name' => 'Gartenzimmer', 'squareMeters' => 14],
            'large' => ['name' => 'Panoramazimmer', 'squareMeters' => 18],
        ] as $key => $definition) {
            $room = new Room();
            $room->setName($definition['name']);
            $room->setSquareMeters($definition['squareMeters']);
            $this->em->persist($room);
            $this->stampCreatedAt($room, $this->sequenceTime($this->hotelRoomSequence, -6));
            $rooms[$key] = $room;
            $manifestRooms[$key] = [
                'id' => $room->getId(),
                'name' => $room->getName(),
                'squareMeters' => $room->getSquareMeters(),
            ];
        }

        foreach ([
            'request_review' => [
                'customerKey' => 'customer_contracts',
                'dogIndex' => 0,
                'roomKey' => null,
                'state' => HotelBookingState::REQUESTED,
                'startAt' => '2026-04-08T09:00:00+02:00',
                'endAt' => '2026-04-09T10:00:00+02:00',
                'includesTravelProtection' => false,
                'extraPriceCents' => 0,
            ],
            'declined' => [
                'customerKey' => 'customer_contract_decline',
                'dogIndex' => 0,
                'roomKey' => null,
                'state' => HotelBookingState::DECLINED,
                'startAt' => '2026-04-09T08:00:00+02:00',
                'endAt' => '2026-04-09T18:00:00+02:00',
                'includesTravelProtection' => false,
                'extraPriceCents' => 0,
            ],
            'small_future' => [
                'customerKey' => 'customer_contract_approve',
                'dogIndex' => 0,
                'roomKey' => 'small',
                'state' => HotelBookingState::CONFIRMED,
                'startAt' => '2026-04-07T08:00:00+02:00',
                'endAt' => '2026-04-07T18:00:00+02:00',
                'includesTravelProtection' => false,
                'extraPriceCents' => 0,
            ],
            'medium_first' => [
                'customerKey' => 'customer_multi_dog',
                'dogIndex' => 0,
                'roomKey' => 'medium',
                'state' => HotelBookingState::CONFIRMED,
                'startAt' => '2026-04-06T10:00:00+02:00',
                'endAt' => '2026-04-06T18:00:00+02:00',
                'includesTravelProtection' => false,
                'extraPriceCents' => 0,
            ],
            'medium_second' => [
                'customerKey' => 'customer_dashboard',
                'dogIndex' => 0,
                'roomKey' => 'medium',
                'state' => HotelBookingState::CONFIRMED,
                'startAt' => '2026-04-06T12:00:00+02:00',
                'endAt' => '2026-04-06T20:00:00+02:00',
                'includesTravelProtection' => false,
                'extraPriceCents' => 0,
            ],
            'large_stay' => [
                'customerKey' => 'customer_profile',
                'dogIndex' => 0,
                'roomKey' => 'large',
                'state' => HotelBookingState::CONFIRMED,
                'startAt' => '2026-04-06T11:00:00+02:00',
                'endAt' => '2026-04-07T07:00:00+02:00',
                'includesTravelProtection' => true,
                'extraPriceCents' => 0,
            ],
            'pending_customer_review' => [
                'customerKey' => 'customer_contract_pending',
                'dogIndex' => 0,
                'roomKey' => 'large',
                'state' => HotelBookingState::PENDING_CUSTOMER_APPROVAL,
                'startAt' => '2026-04-10T09:00:00+02:00',
                'endAt' => '2026-04-12T10:00:00+02:00',
                'includesTravelProtection' => true,
                'extraPriceCents' => 25_00,
            ],
        ] as $key => $definition) {
            $entry = $customers[$definition['customerKey']];
            $booking = new HotelBooking();
            $booking->setCustomer($entry['customer']);
            $booking->setDog($entry['dogs'][$definition['dogIndex']]);
            $booking->setRoom($definition['roomKey'] !== null ? $rooms[$definition['roomKey']] : null);
            $booking->setState($definition['state']);
            $booking->setStartAt(new \DateTimeImmutable($definition['startAt']));
            $booking->setEndAt(new \DateTimeImmutable($definition['endAt']));
            $booking->setIncludesTravelProtection($definition['includesTravelProtection']);
            $booking->setCustomerComment('Bitte moeglichst ruhige Unterbringung.');
            $this->applyHotelPricing($booking, $definition['extraPriceCents']);
            if ($definition['state'] === HotelBookingState::PENDING_CUSTOMER_APPROVAL) {
                $booking->setAdminComment('Preis angepasst wegen manueller Zusatzwuensche.');
            }
            $this->em->persist($booking);
            $this->stampCreatedAt($booking, $this->sequenceTime($this->hotelBookingSequence, -8));
            $manifestHotelBookings[$key] = $booking->getId();
        }
    }

    private function createPricingConfig(): void
    {
        if ($this->em->getRepository(PricingConfig::class)->findOneBy([]) instanceof PricingConfig) {
            return;
        }

        $this->em->persist($this->createDefaultPricingConfigEntity());
    }

    private function applyContractPricing(Contract $contract, int $extraMonthlyCents = 0, bool $hasRegistrationFee = true): void
    {
        $monthlyPriceCents = $this->resolveDefaultSchoolMonthlyPriceCents($contract->getCoursesPerWeek());
        $quotedMonthlyPrice = PricingEngine::formatAmount($monthlyPriceCents);
        $finalMonthlyPrice = PricingEngine::formatAmount($monthlyPriceCents + $extraMonthlyCents);
        $registrationFee = PricingEngine::formatAmount($hasRegistrationFee ? $this->resolveDefaultRegistrationFeeCents() : 0);

        $contract->setQuotedMonthlyPrice($quotedMonthlyPrice);
        $contract->setPrice($finalMonthlyPrice);
        $contract->setRegistrationFee($registrationFee);
        $contract->setPricingSnapshot(PricingEngine::finalizeContractSnapshot([
            'type' => 'contract',
            'coursesPerWeek' => $contract->getCoursesPerWeek(),
            'monthlyUnitPrice' => PricingEngine::schoolUnitPriceForCourseCount(new PricingConfig(), $contract->getCoursesPerWeek()),
            'monthlyPrice' => $quotedMonthlyPrice,
            'registrationFee' => $registrationFee,
            'firstInvoiceTotal' => PricingEngine::formatAmount(
                PricingEngine::amountToCents($quotedMonthlyPrice) + PricingEngine::amountToCents($registrationFee),
            ),
            'lineItems' => [
                [
                    'key' => 'school_contract_monthly',
                    'label' => sprintf('%dx Training pro Woche', $contract->getCoursesPerWeek()),
                    'quantity' => $contract->getCoursesPerWeek(),
                    'unitPrice' => PricingEngine::formatAmount(intdiv($monthlyPriceCents, $contract->getCoursesPerWeek())),
                    'amount' => $quotedMonthlyPrice,
                    'billingPeriod' => 'MONTH',
                ],
                [
                    'key' => 'school_registration_fee',
                    'label' => 'Anmeldegebühr',
                    'quantity' => 1,
                    'unitPrice' => $registrationFee,
                    'amount' => $registrationFee,
                    'billingPeriod' => 'ONCE',
                ],
            ],
        ], $finalMonthlyPrice, $registrationFee));
    }

    private function applyHotelPricing(HotelBooking $booking, int $extraPriceCents = 0): void
    {
        $pricingKind = $booking->getStartAt()->format('Y-m-d') === $booking->getEndAt()->format('Y-m-d')
            ? HotelBookingPricingKind::DAYCARE
            : HotelBookingPricingKind::HOTEL;
        $billableDays = PricingEngine::billableCalendarDays($booking->getStartAt(), $booking->getEndAt());
        $baseDailyPriceCents = match ($pricingKind) {
            HotelBookingPricingKind::DAYCARE => $this->isPeakSeasonDate($booking->getStartAt()) ? 46_00 : 39_00,
            HotelBookingPricingKind::HOTEL => 58_00,
        };
        $baseAmountCents = $baseDailyPriceCents * $billableDays;
        $serviceFeeCents = 7_50;
        $travelProtectionCents = $booking->includesTravelProtection()
            ? 49_00 + (max(0, $billableDays - 7) * 11_00)
            : 0;
        $quotedTotalCents = $baseAmountCents + $serviceFeeCents + $travelProtectionCents;

        $booking->setPricingKind($pricingKind);
        $booking->setBillableDays($billableDays);
        $booking->setQuotedTotalPrice(PricingEngine::formatAmount($quotedTotalCents));
        $booking->setTotalPrice(PricingEngine::formatAmount($quotedTotalCents + $extraPriceCents));
        $booking->setServiceFee(PricingEngine::formatAmount($serviceFeeCents));
        $booking->setTravelProtectionPrice(PricingEngine::formatAmount($travelProtectionCents));
        $booking->setPricingSnapshot(PricingEngine::finalizeHotelBookingSnapshot([
            'type' => 'hotelBooking',
            'pricingKind' => $pricingKind->value,
            'billableDays' => $billableDays,
            'quotedTotalPrice' => PricingEngine::formatAmount($quotedTotalCents),
            'lineItems' => [
                [
                    'key' => 'hotel_base',
                    'label' => $pricingKind === HotelBookingPricingKind::DAYCARE
                        ? sprintf('HUTA %s', $this->isPeakSeasonDate($booking->getStartAt()) ? 'Hauptsaison' : 'Nebensaison')
                        : 'Hundehotel',
                    'quantity' => $billableDays,
                    'unitPrice' => PricingEngine::formatAmount($baseDailyPriceCents),
                    'amount' => PricingEngine::formatAmount($baseAmountCents),
                    'billingPeriod' => 'DAY',
                ],
                [
                    'key' => 'hotel_service_fee',
                    'label' => 'Servicepauschale',
                    'quantity' => 1,
                    'unitPrice' => '7.50',
                    'amount' => '7.50',
                    'billingPeriod' => 'ONCE',
                ],
                [
                    'key' => 'hotel_travel_protection',
                    'label' => 'Reiseschutz',
                    'quantity' => $booking->includesTravelProtection() ? 1 : 0,
                    'unitPrice' => PricingEngine::formatAmount($travelProtectionCents),
                    'amount' => PricingEngine::formatAmount($travelProtectionCents),
                    'billingPeriod' => 'ONCE',
                ],
            ],
        ], $booking->getTotalPrice()));
    }

    private function createDefaultPricingConfigEntity(): PricingConfig
    {
        $pricingConfig = new PricingConfig();
        foreach (PricingConfigProvider::defaultPeakSeasonRanges() as [$startDate, $endDate]) {
            $season = new HotelPeakSeason();
            $season->setStartDate(new \DateTimeImmutable($startDate));
            $season->setEndDate(new \DateTimeImmutable($endDate));
            $pricingConfig->addHotelPeakSeason($season);
        }

        return $pricingConfig;
    }

    private function resolveDefaultSchoolMonthlyPriceCents(int $coursesPerWeek): int
    {
        $normalizedCoursesPerWeek = max(1, $coursesPerWeek);
        $pricingConfig = new PricingConfig();
        $unitPrice = PricingEngine::schoolUnitPriceForCourseCount($pricingConfig, $normalizedCoursesPerWeek);

        return PricingEngine::amountToCents($unitPrice) * $normalizedCoursesPerWeek;
    }

    private function resolveDefaultRegistrationFeeCents(): int
    {
        return PricingEngine::amountToCents((new PricingConfig())->getSchoolRegistrationFee());
    }

    private function isPeakSeasonDate(\DateTimeImmutable $date): bool
    {
        foreach (PricingConfigProvider::defaultPeakSeasonRanges() as [$startDate, $endDate]) {
            $start = new \DateTimeImmutable($startDate);
            $end = new \DateTimeImmutable($endDate);
            if ($date >= $start && $date <= $end->setTime(23, 59, 59)) {
                return true;
            }
        }

        return false;
    }

    private function sequenceTime(int &$sequence, int $stepMinutes = -1): \DateTimeImmutable
    {
        $time = $this->referenceNow()->modify(sprintf('%+d minutes', $sequence * $stepMinutes));
        ++$sequence;

        return $time;
    }

    private function stampCreatedAt(object $entity, \DateTimeImmutable $value): void
    {
        $property = new \ReflectionProperty($entity, 'createdAt');
        $property->setValue($entity, $value);
    }
}
