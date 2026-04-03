<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\Contract;
use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\CreditTransaction;
use App\Entity\Customer;
use App\Entity\Dog;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\ContractState;
use App\Enum\ContractType;
use App\Enum\CreditTransactionType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DemoFixtures extends Fixture implements DependentFixtureInterface
{
    private const PASSWORD = 'test1234';

    // -----------------------------------------------------------------------
    // Customer data pools
    // -----------------------------------------------------------------------

    private const CUSTOMER_NAMES = [
        'Anna Schmidt', 'Max Müller', 'Sarah Weber', 'Thomas Fischer', 'Julia Becker',
        'Michael Wagner', 'Laura Hofmann', 'Daniel Schäfer', 'Lisa Koch', 'Markus Richter',
        'Katrin Klein', 'Stefan Wolf', 'Sandra Schröder', 'Christian Neumann', 'Nicole Schwarz',
        'Andreas Zimmermann', 'Sabine Braun', 'Martin Hartmann', 'Monika Krüger', 'Frank Werner',
        'Claudia Lange', 'Jan Schmitt', 'Petra Meier', 'Tobias Schmitz', 'Birgit Krause',
        'Sven Peters', 'Heike Scholz', 'Patrick Herrmann', 'Anja Friedrich', 'Florian König',
        'Nadine Walter', 'Oliver Schulz', 'Susanne Mayer', 'Matthias Huber', 'Melanie Jung',
        'Jens Hahn', 'Kerstin Frank', 'Philipp Lehmann', 'Simone Berger', 'Thorsten Kaiser',
        'Christina Vogel', 'Sebastian Weiß', 'Tanja Baumann', 'Marco Albrecht', 'Silke Brandt',
        'Dennis Henkel', 'Stefanie Reuter', 'Alexander Lorenz', 'Franziska Engel', 'Hendrik Böhm',
    ];

    private const STREETS = [
        'Brock 5', 'Münsterstr. 12', 'Dorfstr. 8', 'Bahnhofstr. 22', 'Gartenweg 3',
        'Am Markt 7', 'Schulstr. 15', 'Lindenallee 9', 'Kirchweg 4', 'Hauptstr. 31',
        'Feldstr. 18', 'Waldweg 2', 'Parkstr. 11', 'Rosenweg 6', 'Birkenstr. 14',
        'Eichenstr. 27', 'Mühlenweg 10', 'Weseler Str. 45', 'Dülmener Str. 33', 'Holtstr. 19',
        'Steverstr. 7', 'Am Kanal 1', 'Grevener Str. 88', 'Hammer Str. 42', 'Warendorfer Str. 16',
    ];

    /** @var list<array{string, string}> [postalCode, city] */
    private const CITIES = [
        ['48308', 'Senden'], ['48163', 'Münster'], ['48249', 'Dülmen'],
        ['48231', 'Warendorf'], ['48291', 'Telgte'], ['48268', 'Greven'],
        ['48282', 'Emsdetten'], ['48653', 'Coesfeld'], ['59348', 'Lüdinghausen'],
        ['48301', 'Nottuln'], ['48329', 'Havixbeck'], ['59387', 'Ascheberg'],
        ['48143', 'Münster'], ['48155', 'Münster'], ['48159', 'Münster'],
    ];

    private const IBANS = [
        'DE89370400440532013000', 'DE27100777770209299700', 'DE44500105175407324931',
        'DE35500105172699389831', 'DE69500105179536954242', 'DE71500105174185927316',
        'DE18500105173516826117', 'DE86500105178294765428',
    ];

    private const BICS = [
        'COBADEFFXXX', 'DEUTDEFFXXX', 'INGDDEFFXXX', 'GENODEFFXXX', 'BYLADEM1001', 'NOLADE21HAM',
    ];

    // -----------------------------------------------------------------------
    // Dog data pool — [name, breed, gender, color]
    // -----------------------------------------------------------------------

    private const DOG_PROFILES = [
        ['Bella', 'Golden Retriever', 'female', 'Golden'],
        ['Rex', 'Deutscher Schäferhund', 'male', 'Schwarz-Braun'],
        ['Luna', 'Labrador', 'female', 'Schokobraun'],
        ['Buddy', 'Beagle', 'male', 'Tricolor'],
        ['Nala', 'Border Collie', 'female', 'Schwarz-Weiß'],
        ['Charlie', 'Australian Shepherd', 'male', 'Blue Merle'],
        ['Daisy', 'Pudel', 'female', 'Weiß'],
        ['Rocky', 'Boxer', 'male', 'Braun'],
        ['Mia', 'Jack Russell Terrier', 'female', 'Weiß-Braun'],
        ['Oscar', 'Dackel', 'male', 'Rot'],
        ['Lilly', 'Cavalier King Charles Spaniel', 'female', 'Blenheim'],
        ['Bruno', 'Rottweiler', 'male', 'Schwarz-Braun'],
        ['Emma', 'Shih Tzu', 'female', 'Gold-Weiß'],
        ['Balu', 'Berner Sennenhund', 'male', 'Dreifarbig'],
        ['Coco', 'Cocker Spaniel', 'female', 'Schwarz'],
        ['Sammy', 'Havaneser', 'male', 'Creme'],
        ['Lotta', 'Malteser', 'female', 'Weiß'],
        ['Finn', 'Irish Setter', 'male', 'Rot'],
        ['Greta', 'Weimaraner', 'female', 'Silbergrau'],
        ['Leo', 'Rhodesian Ridgeback', 'male', 'Weizenfarben'],
        ['Amy', 'Yorkshire Terrier', 'female', 'Stahlblau-Tan'],
        ['Maxl', 'Dalmatiner', 'male', 'Weiß-Schwarz'],
        ['Zoe', 'Französische Bulldogge', 'female', 'Fawn'],
        ['Sam', 'Husky', 'male', 'Grau-Weiß'],
        ['Kira', 'Dobermann', 'female', 'Schwarz-Braun'],
        ['Lucky', 'Mops', 'male', 'Beige'],
        ['Bonnie', 'Collie', 'female', 'Zobel-Weiß'],
        ['Teddy', 'Zwergspitz', 'male', 'Orange'],
        ['Pia', 'Schipperke', 'female', 'Schwarz'],
        ['Zeus', 'Deutsche Dogge', 'male', 'Blau'],
        ['Frieda', 'Whippet', 'female', 'Gestromt'],
        ['Benno', 'Flat-Coated Retriever', 'male', 'Schwarz'],
        ['Mila', 'Miniatur Bullterrier', 'female', 'Weiß-Gestromt'],
        ['Anton', 'Eurasier', 'male', 'Rot-Grau'],
        ['Romy', 'Nova Scotia Duck Tolling Retriever', 'female', 'Rot-Weiß'],
        ['Oskar', 'Kleiner Münsterländer', 'male', 'Braun-Weiß'],
        ['Finja', 'Lagotto Romagnolo', 'female', 'Braun'],
        ['Carlo', 'Vizsla', 'male', 'Goldrost'],
        ['Nelly', 'English Springer Spaniel', 'female', 'Leber-Weiß'],
        ['Moritz', 'Airedale Terrier', 'male', 'Loh-Schwarz'],
        ['Wilma', 'Briard', 'female', 'Fawn'],
        ['Hugo', 'Leonberger', 'male', 'Löwengelb'],
        ['Elsa', 'Samojede', 'female', 'Weiß'],
        ['Theo', 'Parson Russell Terrier', 'male', 'Weiß-Braun'],
        ['Lina', 'Basenji', 'female', 'Rot-Weiß'],
        ['Fritz', 'Schnauzer', 'male', 'Pfeffersalz'],
        ['Hanna', 'Zwergpudel', 'female', 'Apricot'],
        ['Ole', 'Großer Schweizer Sennenhund', 'male', 'Dreifarbig'],
        ['Leni', 'Welsh Corgi Pembroke', 'female', 'Rot-Weiß'],
        ['Pepe', 'Chihuahua', 'male', 'Creme'],
    ];

    // -----------------------------------------------------------------------
    // Weekly course schedule — [typeCode, dayOfWeek, startTime, endTime, level]
    //
    // 36 courses across Mon–Sat using all 13 course types.
    //   Mon: 5 | Tue: 6 | Wed: 5 | Thu: 6 | Fri: 6 | Sat: 8
    // -----------------------------------------------------------------------

    private const COURSE_DEFS = [
        // --- Monday (5) ---
        ['MH',   1, '10:00', '11:00', 1],  //  0
        ['MT',   1, '14:00', '15:00', 0],  //  1
        ['DIA',  1, '16:00', '17:00', 1],  //  2
        ['AGI',  1, '18:00', '19:00', 1],  //  3
        ['JUHU', 1, '19:00', '20:00', 0],  //  4
        // --- Tuesday (6) ---
        ['APP',  2, '10:00', '11:00', 1],  //  5
        ['MH',   2, '11:00', '12:00', 2],  //  6
        ['RO',   2, '16:00', '17:00', 1],  //  7
        ['JUHU', 2, '17:00', '18:00', 0],  //  8
        ['DF',   2, '18:00', '19:00', 0],  //  9
        ['AGI',  2, '19:00', '20:00', 2],  // 10
        // --- Wednesday (5) ---
        ['MH',   3, '10:00', '11:00', 0],  // 11
        ['MT',   3, '14:00', '15:00', 1],  // 12
        ['CC',   3, '16:00', '17:00', 1],  // 13
        ['TK',   3, '18:00', '19:00', 0],  // 14
        ['AGI',  3, '19:00', '20:00', 3],  // 15
        // --- Thursday (6) ---
        ['APP',  4, '10:00', '11:00', 2],  // 16
        ['MH',   4, '11:00', '12:00', 1],  // 17
        ['DIA',  4, '16:00', '17:00', 2],  // 18
        ['JUHU', 4, '17:00', '18:00', 0],  // 19
        ['TK',   4, '18:00', '19:00', 1],  // 20
        ['THS',  4, '19:00', '20:00', 1],  // 21
        // --- Friday (6) ---
        ['TK',   5, '09:00', '10:00', 2],  // 22
        ['MH',   5, '10:00', '11:00', 2],  // 23
        ['THS',  5, '12:00', '13:00', 2],  // 24
        ['FSTS', 5, '14:00', '15:00', 2],  // 25
        ['AGI',  5, '16:00', '17:00', 1],  // 26
        ['MT',   5, '18:00', '19:00', 2],  // 27
        // --- Saturday (8) ---
        ['MT',   6, '08:00', '09:00', 0],  // 28
        ['MH',   6, '09:00', '10:00', 4],  // 29
        ['JUHU', 6, '10:00', '11:00', 0],  // 30
        ['CC',   6, '10:00', '11:00', 2],  // 31
        ['RO',   6, '11:00', '12:00', 2],  // 32
        ['DS',   6, '12:00', '13:00', 1],  // 33
        ['AGI',  6, '14:00', '15:00', 2],  // 34
        ['DF',   6, '15:00', '16:00', 1],  // 35
    ];

    /** @var Contract[] */
    private array $activeContracts = [];

    /** @var array<int, int[]> customer index → subscribed course indices */
    private array $subscriptionMap = [];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $courseTypes = $this->indexCourseTypes($manager);
        $trainers = $this->indexTrainers($manager);

        $customers = $this->createCustomers($manager);
        $courses = $this->createCourses($manager, $courseTypes, $trainers);
        $courseDates = $this->createCourseDates($manager, $courses);
        $this->createSubscriptions($manager, $customers, $courses);
        $this->createContracts($manager, $customers);
        $this->createCreditTransactions($manager);
        $this->createBookings($manager, $customers, $courseDates);
        $this->createNotifications($manager, $courses, $trainers);

        $manager->flush();
    }

    // -----------------------------------------------------------------------
    // Indexing helpers
    // -----------------------------------------------------------------------

    /** @return array<string, CourseType> */
    private function indexCourseTypes(ObjectManager $manager): array
    {
        $types = $manager->getRepository(CourseType::class)->findAll();
        $indexed = [];
        foreach ($types as $t) {
            $indexed[$t->getCode()] = $t;
        }

        return $indexed;
    }

    /** @return array<string, User> */
    private function indexTrainers(ObjectManager $manager): array
    {
        $users = $manager->getRepository(User::class)->findAll();
        $indexed = [];
        foreach ($users as $u) {
            $indexed[$u->getUsername()] = $u;
        }

        return $indexed;
    }

    // -----------------------------------------------------------------------
    // Customers + Dogs (50 customers, ~68 dogs)
    // -----------------------------------------------------------------------

    /** @return array<int, array{customer: Customer, dogs: Dog[]}> */
    private function createCustomers(ObjectManager $manager): array
    {
        $profileCount = \count(self::DOG_PROFILES);
        $streetCount = \count(self::STREETS);
        $cityCount = \count(self::CITIES);
        $ibanCount = \count(self::IBANS);
        $bicCount = \count(self::BICS);

        $result = [];
        $dogIdx = 0;

        foreach (self::CUSTOMER_NAMES as $i => $name) {
            $customer = new Customer();
            $customer->setName($name);
            $customer->setEmail(self::nameToEmail($name));
            $customer->setPassword($this->passwordHasher->hashPassword($customer, self::PASSWORD));

            $addr = $customer->getAddress();
            $addr->setStreet(self::STREETS[$i % $streetCount]);
            [$postalCode, $city] = self::CITIES[$i % $cityCount];
            $addr->setPostalCode($postalCode);
            $addr->setCity($city);
            $addr->setCountry('DE');

            if ($i % 5 < 3) {
                $bank = $customer->getBankAccount();
                $bank->setIban(self::IBANS[$i % $ibanCount]);
                $bank->setBic(self::BICS[$i % $bicCount]);
                $bank->setAccountHolder($name);
            }

            $manager->persist($customer);

            $dogCount = match (true) {
                $i < 35 => 1,
                $i < 47 => 2,
                default => 3,
            };

            $dogs = [];
            for ($d = 0; $d < $dogCount; ++$d) {
                [$dogName, $breed, $gender, $color] = self::DOG_PROFILES[$dogIdx % $profileCount];
                $dog = new Dog();
                $dog->setName($dogName);
                $dog->setRace($breed);
                $dog->setGender($gender);
                $dog->setColor($color);
                $customer->addDog($dog);
                $manager->persist($dog);
                $dogs[] = $dog;
                ++$dogIdx;
            }

            $result[$i] = ['customer' => $customer, 'dogs' => $dogs];
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Courses (36 weekly slots)
    // -----------------------------------------------------------------------

    /**
     * @param array<string, CourseType> $courseTypes
     * @param array<string, User>       $trainers
     *
     * @return Course[]
     */
    private function createCourses(ObjectManager $manager, array $courseTypes, array $trainers): array
    {
        $courses = [];
        $trainerPool = array_values($trainers);
        foreach (self::COURSE_DEFS as [$typeCode, $dow, $start, $end, $level]) {
            if (!isset($courseTypes[$typeCode])) {
                continue;
            }
            $c = new Course();
            $c->setCourseType($courseTypes[$typeCode]);
            $c->setDayOfWeek($dow);
            $c->setStartTime($start);
            $c->setEndTime($end);
            $c->setLevel($level);
            if ($trainerPool !== []) {
                $c->setTrainer($trainerPool[count($courses) % count($trainerPool)]);
            }
            $c->computeDurationMinutes();
            $manager->persist($c);
            $courses[] = $c;
        }

        return $courses;
    }

    // -----------------------------------------------------------------------
    // Course dates (current week ± 1 week, + 1 week ahead)
    // -----------------------------------------------------------------------

    /**
     * @param Course[] $courses
     *
     * @return array<int, CourseDate[]> keyed by course index
     */
    private function createCourseDates(ObjectManager $manager, array $courses): array
    {
        $now = new \DateTimeImmutable('monday this week');
        $from = $now->modify('-1 week');
        $until = $now->modify('+2 weeks');

        $allDates = [];
        foreach ($courses as $idx => $course) {
            $dow = $course->getDayOfWeek();
            $phpDayName = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$dow - 1];

            $current = $from;
            $currentDow = (int) $current->format('N');
            if ($currentDow !== $dow) {
                $current = $current->modify("next {$phpDayName}");
            }

            $dates = [];
            while ($current <= $until) {
                $cd = new CourseDate();
                $cd->setCourse($course);
                $cd->setTrainer($course->getTrainer());
                $cd->setDate($current);
                $cd->setStartTime($course->getStartTime());
                $cd->setEndTime($course->getEndTime());
                $manager->persist($cd);
                $dates[] = $cd;
                $current = $current->modify('+1 week');
            }
            $allDates[$idx] = $dates;
        }

        return $allDates;
    }

    // -----------------------------------------------------------------------
    // Subscriptions (1–4 courses per customer, deterministic spread)
    // -----------------------------------------------------------------------

    /**
     * @param array<int, array{customer: Customer, dogs: Dog[]}> $customers
     * @param Course[]                                           $courses
     */
    private function createSubscriptions(ObjectManager $manager, array $customers, array $courses): void
    {
        $courseCount = \count($courses);
        if ($courseCount === 0) {
            return;
        }

        foreach ($customers as $i => $entry) {
            $subCount = 1 + ($i % 4);
            $this->subscriptionMap[$i] = [];

            for ($s = 0; $s < $subCount; ++$s) {
                $courseIdx = ($i * 7 + $s * 13) % $courseCount;
                $entry['customer']->addSubscribedCourse($courses[$courseIdx]);
                $this->subscriptionMap[$i][] = $courseIdx;
            }
        }
    }

    // -----------------------------------------------------------------------
    // Contracts (40 ACTIVE, 5 REQUESTED, 3 DECLINED, 2 CANCELLED)
    // -----------------------------------------------------------------------

    /**
     * @param array<int, array{customer: Customer, dogs: Dog[]}> $customers
     */
    private function createContracts(ObjectManager $manager, array $customers): void
    {
        $today = new \DateTimeImmutable();

        foreach ($customers as $i => $entry) {
            $state = match (true) {
                $i < 40 => ContractState::ACTIVE,
                $i < 45 => ContractState::REQUESTED,
                $i < 48 => ContractState::DECLINED,
                default => ContractState::CANCELLED,
            };

            $coursesPerWeek = 1 + ($i % 3);
            $price = match ($coursesPerWeek) {
                1 => '59.00',
                2 => '89.00',
                default => '119.00',
            };

            $startOffset = match (true) {
                $i < 15 => '-6 months',
                $i < 30 => '-3 months',
                $i < 40 => '-1 month',
                default => '-2 weeks',
            };

            $contract = $this->makeContract(
                $entry['customer'],
                $entry['dogs'][0],
                $state,
                $coursesPerWeek,
                $price,
                $today->modify($startOffset),
            );
            $manager->persist($contract);

            if ($state === ContractState::ACTIVE) {
                $this->activeContracts[] = $contract;
            }
        }
    }

    private function makeContract(
        Customer $customer,
        Dog $dog,
        ContractState $state,
        int $coursesPerWeek,
        string $price,
        \DateTimeImmutable $startDate,
    ): Contract {
        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState($state);
        $contract->setCoursesPerWeek($coursesPerWeek);
        $contract->setPrice($price);
        $contract->setStartDate($startDate);
        $contract->setEndDate($startDate->modify('+1 year'));
        $contract->setType(ContractType::PERPETUAL);

        return $contract;
    }

    // -----------------------------------------------------------------------
    // Credit transactions (weekly grants for active contracts, 3 weeks)
    // -----------------------------------------------------------------------

    private function createCreditTransactions(ObjectManager $manager): void
    {
        foreach ($this->activeContracts as $contract) {
            $customer = $contract->getCustomer();
            if ($customer === null) {
                continue;
            }

            for ($w = -1; $w <= 1; ++$w) {
                $weekDate = (new \DateTimeImmutable())->modify("{$w} week");
                $weekRef = $weekDate->format('o-\\WW');

                $tx = new CreditTransaction();
                $tx->setCustomer($customer);
                $tx->setAmount($contract->getCoursesPerWeek());
                $tx->setType(CreditTransactionType::WEEKLY_GRANT);
                $tx->setContract($contract);
                $tx->setWeekRef($weekRef);
                $tx->setDescription(sprintf(
                    'Wöchentliche Credits (%d) für Woche %s',
                    $contract->getCoursesPerWeek(),
                    $weekRef,
                ));
                $manager->persist($tx);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Bookings (active customers book ~65 % of their first subscription,
    //           ~40 % of additional subscriptions, current week only)
    // -----------------------------------------------------------------------

    /**
     * @param array<int, array{customer: Customer, dogs: Dog[]}> $customers
     * @param array<int, CourseDate[]>                           $courseDates
     */
    private function createBookings(ObjectManager $manager, array $customers, array $courseDates): void
    {
        foreach ($customers as $i => $entry) {
            if ($i >= 40) {
                break;
            }

            $courseIndices = $this->subscriptionMap[$i] ?? [];
            $dog = $entry['dogs'][0];

            foreach ($courseIndices as $s => $courseIdx) {
                $threshold = $s === 0 ? 65 : 40;
                if (($i * 31 + $courseIdx * 17) % 100 >= $threshold) {
                    continue;
                }
                $this->book($manager, $entry['customer'], $dog, $courseDates[$courseIdx][1] ?? null);
            }
        }
    }

    private function book(ObjectManager $manager, Customer $customer, Dog $dog, ?CourseDate $courseDate): void
    {
        if ($courseDate === null) {
            return;
        }

        $tx = new CreditTransaction();
        $tx->setCustomer($customer);
        $tx->setAmount(-1);
        $tx->setType(CreditTransactionType::BOOKING);
        $tx->setCourseDate($courseDate);
        $tx->setDescription(sprintf(
            'Gebucht: %s am %s (%s)',
            $courseDate->getCourse()?->getCourseType()?->getName() ?? 'Kurs',
            $courseDate->getDate()->format('d.m.Y'),
            $dog->getName(),
        ));
        $manager->persist($tx);

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setCourseDate($courseDate);
        $booking->setDog($dog);
        $booking->setCreditTransaction($tx);
        $manager->persist($booking);
    }

    // -----------------------------------------------------------------------
    // Notifications (11 notifications, 3 pinned, using all trainers)
    // -----------------------------------------------------------------------

    /**
     * @param Course[]            $courses
     * @param array<string, User> $trainers
     */
    private function createNotifications(ObjectManager $manager, array $courses, array $trainers): void
    {
        $florian = $trainers['florian'] ?? null;
        $manuela = $trainers['manuela'] ?? null;
        $caro = $trainers['caro'] ?? null;
        $lea = $trainers['lea'] ?? null;

        if ($florian === null) {
            return;
        }

        // MH Mon: Trainingsplatz-Änderung
        $n = new Notification();
        $n->setTitle('Trainingsplatz-Änderung');
        $n->setMessage("Liebe Kursteilnehmer,\n\nab nächster Woche trainieren wir auf dem neuen Platz hinter dem Hundehotel. Bitte parkt wie gewohnt auf dem Hauptparkplatz.\n\nViele Grüße,\nFlorian");
        $n->setAuthor($florian);
        if (isset($courses[0])) {
            $n->addCourse($courses[0]);
        }
        $manager->persist($n);

        // All AGI: Parcours erneuert
        $n = new Notification();
        $n->setTitle('Agility-Parcours erneuert');
        $n->setMessage("Hallo zusammen,\n\nwir haben neue Hindernisse für den Agility-Parcours bekommen! Tunnel, Wippe und Slalom sind alle brandneu. Freut euch auf das nächste Training!\n\nEuer Komm!-Team");
        $n->setAuthor($manuela ?? $florian);
        foreach ([3, 10, 15, 26, 34] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $manager->persist($n);

        // JUHU + TK: Sommerpause-Info
        $n = new Notification();
        $n->setTitle('Sommerpause-Info');
        $n->setMessage("Liebe Hundefreunde,\n\nin der letzten Juli-Woche und den ersten zwei August-Wochen findet kein regulärer Kurs statt. Das Hundehotel bleibt geöffnet.\n\nSchöne Grüße,\nEuer Komm!-Team");
        $n->setAuthor($florian);
        foreach ([4, 8, 19, 30, 14, 20, 22] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $manager->persist($n);

        // TK Wed: Willkommen
        $n = new Notification();
        $n->setTitle('Willkommen beim Trickkurs!');
        $n->setMessage("Hallo und herzlich willkommen im Trickkurs!\n\nBringt bitte Leckerlis und ein Lieblingsspielzeug eures Hundes mit. Wir starten mit einfachen Tricks wie Pfote geben und Drehen.\n\nBis Mittwoch!");
        $n->setAuthor($manuela ?? $florian);
        if (isset($courses[14])) {
            $n->addCourse($courses[14]);
        }
        $manager->persist($n);

        // Global: Frohe Ostern!
        $n = new Notification();
        $n->setTitle('Frohe Ostern!');
        $n->setMessage("Liebe Hundefreunde,\n\nwir wünschen euch und euren Vierbeinern ein wunderschönes Osterfest! 🐣🐕\n\nBitte beachtet: Am Ostermontag finden keine Kurse statt.\n\nEuer Komm!-Team");
        $n->setAuthor($florian);
        $manager->persist($n);

        // Global pinned: Sommerferien
        $n = new Notification();
        $n->setTitle('Sommerferien: 28.07. – 15.08.');
        $n->setMessage("Liebe Hundefreunde,\n\nvom 28. Juli bis 15. August findet kein Kursbetrieb statt. Wir genießen die Sommerpause und sind ab dem 18. August wieder für euch da!\n\nBitte plant entsprechend. Bei Fragen meldet euch gerne.\n\nEuer Komm!-Team");
        $n->setAuthor($florian);
        $n->setPinnedUntil(new \DateTimeImmutable('2026-08-15T23:59:59'));
        $manager->persist($n);

        // All MT: Neues Suchgebiet
        $n = new Notification();
        $n->setTitle('Mantrailing: Neues Suchgebiet');
        $n->setMessage("Liebe Mantrailer,\n\nwir haben ein neues Übungsgebiet am Kanal erschlossen! Ab sofort starten wir abwechselnd dort und am gewohnten Platz. Bitte achtet auf die Ansage vor dem jeweiligen Termin.\n\nViele Grüße,\nCaro");
        $n->setAuthor($caro ?? $florian);
        foreach ([1, 12, 27, 28] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $manager->persist($n);

        // CC + DS: Streckenänderung
        $n = new Notification();
        $n->setTitle('Canicross & Dogscooter: Neue Strecke');
        $n->setMessage("Hallo Sportler,\n\ndie Laufstrecke im Wald wurde frisch markiert und leicht verändert. Außerdem haben wir zwei neue Scooter angeschafft, die ihr gerne testen könnt.\n\nViel Spaß beim Training!\nFlorian");
        $n->setAuthor($florian);
        foreach ([13, 31, 33] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $manager->persist($n);

        // All AGI pinned: Turnier-Ankündigung
        $n = new Notification();
        $n->setTitle('Agility-Turnier am 12. April');
        $n->setMessage("Liebe Agility-Teilnehmer,\n\nam 12. April findet unser vereinsinternes Agility-Turnier statt! Anmeldungen bitte bis zum 5. April bei Manuela. Es gibt Pokale in allen Leistungsklassen.\n\nStart: 10:00 Uhr\nOrt: Trainingsgelände\n\nWir freuen uns auf euch!");
        $n->setAuthor($manuela ?? $florian);
        foreach ([3, 10, 15, 26, 34] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $n->setPinnedUntil(new \DateTimeImmutable('+30 days'));
        $manager->persist($n);

        // APP: Neue Dummies
        $n = new Notification();
        $n->setTitle('Apportier-Kurs: Neue Dummies eingetroffen');
        $n->setMessage("Hallo zusammen,\n\nwir haben endlich die neuen Canvas-Dummies in verschiedenen Gewichten bekommen. Bringt gerne auch eure eigenen Dummies mit, dann können wir vergleichen.\n\nBis bald,\nLea");
        $n->setAuthor($lea ?? $florian);
        foreach ([5, 16] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $manager->persist($n);

        // All JUHU pinned: Schnuppertag
        $n = new Notification();
        $n->setTitle('Junghunde-Schnuppertag am 5. April');
        $n->setMessage("Liebe Junghunde-Besitzer,\n\nam 5. April laden wir zum kostenlosen Schnuppertag ein! Bringt gerne Freunde mit Junghunden (bis 12 Monate) mit. Wir zeigen Grundübungen und beantworten eure Fragen.\n\nAnmeldung per E-Mail genügt.\n\nEuer Komm!-Team");
        $n->setAuthor($florian);
        foreach ([4, 8, 19, 30] as $ci) {
            if (isset($courses[$ci])) {
                $n->addCourse($courses[$ci]);
            }
        }
        $n->setPinnedUntil(new \DateTimeImmutable('2026-04-05T23:59:59'));
        $manager->persist($n);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private static function nameToEmail(string $name): string
    {
        $lower = mb_strtolower($name, 'UTF-8');

        return str_replace(
            [' ', 'ä', 'ö', 'ü', 'ß'],
            ['.', 'ae', 'oe', 'ue', 'ss'],
            $lower,
        ).'@example.com';
    }

    /** @return list<class-string<Fixture>> */
    public function getDependencies(): array
    {
        return [CourseTypeFixtures::class, TrainerFixtures::class];
    }
}
