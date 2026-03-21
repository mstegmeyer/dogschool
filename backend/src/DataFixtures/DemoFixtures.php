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

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $courseTypes = $this->indexCourseTypes($manager);
        $trainers = $this->indexTrainers($manager);

        // --- Customers with Dogs ---
        $customers = $this->createCustomers($manager);

        // --- Courses ---
        $courses = $this->createCourses($manager, $courseTypes);

        // --- Course Dates (current week ± 1 week) ---
        $courseDates = $this->createCourseDates($manager, $courses);

        // --- Subscriptions ---
        $this->createSubscriptions($manager, $customers, $courses);

        // --- Contracts ---
        $this->createContracts($manager, $customers);

        // --- Credit Transactions ---
        $this->createCreditTransactions($manager, $customers, $courseDates);

        // --- Bookings ---
        $this->createBookings($manager, $customers, $courseDates);

        // --- Notifications ---
        $this->createNotifications($manager, $courses, $trainers);

        $manager->flush();
    }

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

    /**
     * @return array<string, array{customer: Customer, dogs: Dog[]}>
     */
    private function createCustomers(ObjectManager $manager): array
    {
        $data = [
            'anna' => [
                'name' => 'Anna Schmidt',
                'email' => 'anna@example.com',
                'address' => ['Brock 5', '48308', 'Senden', 'DE'],
                'bank' => ['DE89370400440532013000', 'COBADEFFXXX', 'Anna Schmidt'],
                'dogs' => [['Bella', 'Golden Retriever', 'female', 'Golden']],
            ],
            'max' => [
                'name' => 'Max Müller',
                'email' => 'max@example.com',
                'address' => ['Münsterstr. 12', '48163', 'Münster', 'DE'],
                'bank' => ['DE27100777770209299700', 'DEUTDEFFXXX', 'Max Müller'],
                'dogs' => [
                    ['Rex', 'Deutscher Schäferhund', 'male', 'Schwarz-Braun'],
                    ['Luna', 'Labrador', 'female', 'Schokobraun'],
                ],
            ],
            'sarah' => [
                'name' => 'Sarah Weber',
                'email' => 'sarah@example.com',
                'address' => ['Dorfstr. 8', '48308', 'Senden', 'DE'],
                'bank' => null,
                'dogs' => [['Buddy', 'Beagle', 'male', 'Tricolor']],
            ],
            'thomas' => [
                'name' => 'Thomas Fischer',
                'email' => 'thomas@example.com',
                'address' => ['Bahnhofstr. 22', '48249', 'Dülmen', 'DE'],
                'bank' => ['DE44500105175407324931', 'INGDDEFFXXX', 'Thomas Fischer'],
                'dogs' => [['Nala', 'Border Collie', 'female', 'Schwarz-Weiß']],
            ],
            'julia' => [
                'name' => 'Julia Becker',
                'email' => 'julia@example.com',
                'address' => ['Gartenweg 3', '48153', 'Münster', 'DE'],
                'bank' => null,
                'dogs' => [['Charlie', 'Australian Shepherd', 'male', 'Blue Merle']],
            ],
            'michael' => [
                'name' => 'Michael Wagner',
                'email' => 'michael@example.com',
                'address' => ['Am Markt 7', '48308', 'Senden', 'DE'],
                'bank' => ['DE89370400440532013000', 'COBADEFFXXX', 'Michael Wagner'],
                'dogs' => [['Daisy', 'Pudel', 'female', 'Weiß']],
            ],
            'laura' => [
                'name' => 'Laura Hofmann',
                'email' => 'laura@example.com',
                'address' => ['Schulstr. 15', '48161', 'Münster', 'DE'],
                'bank' => null,
                'dogs' => [['Rocky', 'Boxer', 'male', 'Braun']],
            ],
            'daniel' => [
                'name' => 'Daniel Schäfer',
                'email' => 'daniel@example.com',
                'address' => ['Lindenallee 9', '48231', 'Warendorf', 'DE'],
                'bank' => ['DE44500105175407324931', 'INGDDEFFXXX', 'Daniel Schäfer'],
                'dogs' => [
                    ['Mia', 'Jack Russell Terrier', 'female', 'Weiß-Braun'],
                    ['Oscar', 'Dackel', 'male', 'Rot'],
                ],
            ],
        ];

        $result = [];
        foreach ($data as $key => $d) {
            $customer = new Customer();
            $customer->setName($d['name']);
            $customer->setEmail($d['email']);
            $customer->setPassword($this->passwordHasher->hashPassword($customer, self::PASSWORD));

            $addr = $customer->getAddress();
            $addr->setStreet($d['address'][0]);
            $addr->setPostalCode($d['address'][1]);
            $addr->setCity($d['address'][2]);
            $addr->setCountry($d['address'][3]);

            if ($d['bank'] !== null) {
                $bank = $customer->getBankAccount();
                $bank->setIban($d['bank'][0]);
                $bank->setBic($d['bank'][1]);
                $bank->setAccountHolder($d['bank'][2]);
            }

            $manager->persist($customer);

            $dogs = [];
            foreach ($d['dogs'] as [$name, $race, $gender, $color]) {
                $dog = new Dog();
                $dog->setName($name);
                $dog->setRace($race);
                $dog->setGender($gender);
                $dog->setColor($color);
                $customer->addDog($dog);
                $manager->persist($dog);
                $dogs[] = $dog;
            }

            $result[$key] = ['customer' => $customer, 'dogs' => $dogs];
        }

        return $result;
    }

    /**
     * @param array<string, CourseType> $courseTypes
     * @return Course[]
     */
    private function createCourses(ObjectManager $manager, array $courseTypes): array
    {
        $defs = [
            ['MH', 1, '10:00', '11:00', 1],   // Mensch & Hund, Mon
            ['MH', 3, '10:00', '11:00', 2],   // Mensch & Hund, Wed
            ['JUHU', 2, '17:00', '18:00', 0], // Junghunde, Tue
            ['JUHU', 4, '17:00', '18:00', 0], // Junghunde, Thu
            ['AGI', 1, '18:00', '19:00', 1],  // Agility, Mon
            ['AGI', 5, '16:00', '17:00', 2],  // Agility, Fri
            ['TK', 3, '18:00', '19:00', 0],   // Trickkurs, Wed
            ['CC', 6, '10:00', '11:00', 1],   // Canicross, Sat
            ['RO', 6, '11:00', '12:00', 1],   // Rally Obedience, Sat
            ['APP', 2, '10:00', '11:00', 1],  // Apportieren, Tue
        ];

        $courses = [];
        foreach ($defs as [$typeCode, $dow, $start, $end, $level]) {
            if (!isset($courseTypes[$typeCode])) {
                continue;
            }
            $c = new Course();
            $c->setCourseType($courseTypes[$typeCode]);
            $c->setDayOfWeek($dow);
            $c->setStartTime($start);
            $c->setEndTime($end);
            $c->setLevel($level);
            $c->computeDurationMinutes();
            $manager->persist($c);
            $courses[] = $c;
        }

        return $courses;
    }

    /**
     * @param Course[] $courses
     * @return array<string, CourseDate[]> keyed by course index
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

    /**
     * @param array<string, array{customer: Customer, dogs: Dog[]}> $customers
     * @param Course[] $courses
     */
    private function createSubscriptions(ObjectManager $manager, array $customers, array $courses): void
    {
        // Anna subscribes to MH Mon, AGI Mon
        $customers['anna']['customer']->addSubscribedCourse($courses[0]);
        $customers['anna']['customer']->addSubscribedCourse($courses[4]);

        // Max subscribes to MH Mon, MH Wed, AGI Fri
        $customers['max']['customer']->addSubscribedCourse($courses[0]);
        $customers['max']['customer']->addSubscribedCourse($courses[1]);
        $customers['max']['customer']->addSubscribedCourse($courses[5]);

        // Sarah subscribes to JUHU Tue
        $customers['sarah']['customer']->addSubscribedCourse($courses[2]);

        // Thomas subscribes to AGI Mon, CC Sat
        $customers['thomas']['customer']->addSubscribedCourse($courses[4]);
        $customers['thomas']['customer']->addSubscribedCourse($courses[7]);

        // Julia subscribes to JUHU Thu, TK Wed
        $customers['julia']['customer']->addSubscribedCourse($courses[3]);
        $customers['julia']['customer']->addSubscribedCourse($courses[6]);

        // Michael subscribes to MH Wed
        $customers['michael']['customer']->addSubscribedCourse($courses[1]);

        // Daniel subscribes to APP Tue, RO Sat
        $customers['daniel']['customer']->addSubscribedCourse($courses[9]);
        $customers['daniel']['customer']->addSubscribedCourse($courses[8]);
    }

    /**
     * @param array<string, array{customer: Customer, dogs: Dog[]}> $customers
     */
    private function createContracts(ObjectManager $manager, array $customers): void
    {
        $today = new \DateTimeImmutable();
        $monthAgo = $today->modify('-1 month');
        $threeMonthsAgo = $today->modify('-3 months');

        // Anna: ACTIVE, 2/week, 89€
        $c1 = $this->makeContract($customers['anna']['customer'], $customers['anna']['dogs'][0], ContractState::ACTIVE, 2, '89.00', $threeMonthsAgo);
        $manager->persist($c1);

        // Max: ACTIVE, 3/week, 119€
        $c2 = $this->makeContract($customers['max']['customer'], $customers['max']['dogs'][0], ContractState::ACTIVE, 3, '119.00', $threeMonthsAgo);
        $manager->persist($c2);

        // Sarah: REQUESTED, 2/week, 89€
        $c3 = $this->makeContract($customers['sarah']['customer'], $customers['sarah']['dogs'][0], ContractState::REQUESTED, 2, '89.00', $today);
        $manager->persist($c3);

        // Thomas: ACTIVE, 1/week, 59€
        $c4 = $this->makeContract($customers['thomas']['customer'], $customers['thomas']['dogs'][0], ContractState::ACTIVE, 1, '59.00', $monthAgo);
        $manager->persist($c4);

        // Julia: DECLINED
        $c5 = $this->makeContract($customers['julia']['customer'], $customers['julia']['dogs'][0], ContractState::DECLINED, 2, '89.00', $monthAgo);
        $manager->persist($c5);

        // Laura: CANCELLED
        $c6 = $this->makeContract($customers['laura']['customer'], $customers['laura']['dogs'][0], ContractState::CANCELLED, 2, '89.00', $threeMonthsAgo);
        $manager->persist($c6);

        // Store active contracts for credit granting
        $this->activeContracts = [$c1, $c2, $c4];
    }

    /** @var Contract[] */
    private array $activeContracts = [];

    private function makeContract(Customer $customer, Dog $dog, ContractState $state, int $coursesPerWeek, string $price, \DateTimeImmutable $startDate): Contract
    {
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

    /**
     * @param array<string, array{customer: Customer, dogs: Dog[]}> $customers
     * @param array<int, CourseDate[]> $courseDates
     */
    private function createCreditTransactions(ObjectManager $manager, array $customers, array $courseDates): void
    {
        // Grant weekly credits for active contracts for 3 weeks
        foreach ($this->activeContracts as $contract) {
            $customer = $contract->getCustomer();
            if ($customer === null) continue;

            for ($w = -1; $w <= 1; $w++) {
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

    /**
     * @param array<string, array{customer: Customer, dogs: Dog[]}> $customers
     * @param array<int, CourseDate[]> $courseDates
     */
    private function createBookings(ObjectManager $manager, array $customers, array $courseDates): void
    {
        // Anna books MH Mon (course 0) current week, and AGI Mon (course 4) current week
        $this->book($manager, $customers['anna']['customer'], $customers['anna']['dogs'][0], $courseDates[0][1] ?? null);
        $this->book($manager, $customers['anna']['customer'], $customers['anna']['dogs'][0], $courseDates[4][1] ?? null);

        // Max books MH Mon current week with Rex
        $this->book($manager, $customers['max']['customer'], $customers['max']['dogs'][0], $courseDates[0][1] ?? null);
        // Max books MH Wed current week with Luna
        $this->book($manager, $customers['max']['customer'], $customers['max']['dogs'][1], $courseDates[1][1] ?? null);

        // Thomas books AGI Mon current week
        $this->book($manager, $customers['thomas']['customer'], $customers['thomas']['dogs'][0], $courseDates[4][1] ?? null);
        // Thomas books CC Sat current week
        $this->book($manager, $customers['thomas']['customer'], $customers['thomas']['dogs'][0], $courseDates[7][1] ?? null);
    }

    private function book(ObjectManager $manager, Customer $customer, Dog $dog, ?CourseDate $courseDate): void
    {
        if ($courseDate === null) return;

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

    /**
     * @param Course[] $courses
     * @param array<string, User> $trainers
     */
    private function createNotifications(ObjectManager $manager, array $courses, array $trainers): void
    {
        $florian = $trainers['florian'] ?? null;
        $manuela = $trainers['manuela'] ?? null;

        if ($florian === null) return;

        $n1 = new Notification();
        $n1->setTitle('Trainingsplatz-Änderung');
        $n1->setMessage("Liebe Kursteilnehmer,\n\nab nächster Woche trainieren wir auf dem neuen Platz hinter dem Hundehotel. Bitte parkt wie gewohnt auf dem Hauptparkplatz.\n\nViele Grüße,\nFlorian");
        $n1->setAuthor($florian);
        $n1->addCourse($courses[0]); // MH Mon
        $manager->persist($n1);

        $n2 = new Notification();
        $n2->setTitle('Agility-Parcours erneuert');
        $n2->setMessage("Hallo zusammen,\n\nwir haben neue Hindernisse für den Agility-Parcours bekommen! Tunnel, Wippe und Slalom sind alle brandneu. Freut euch auf das nächste Training!\n\nEuer Komm!-Team");
        $n2->setAuthor($manuela ?? $florian);
        $n2->addCourse($courses[4]); // AGI Mon
        $n2->addCourse($courses[5]); // AGI Fri — same announcement for both Agility courses
        $manager->persist($n2);

        $n3 = new Notification();
        $n3->setTitle('Sommerpause-Info');
        $n3->setMessage("Liebe Hundefreunde,\n\nin der letzten Juli-Woche und den ersten zwei August-Wochen findet kein regulärer Kurs statt. Das Hundehotel bleibt geöffnet.\n\nSchöne Grüße,\nEuer Komm!-Team");
        $n3->setAuthor($florian);
        $n3->addCourse($courses[2]); // JUHU Tue
        $n3->addCourse($courses[3]); // JUHU Thu
        $n3->addCourse($courses[6]); // TK Wed
        $manager->persist($n3);

        $n4 = new Notification();
        $n4->setTitle('Willkommen beim Trickkurs!');
        $n4->setMessage("Hallo und herzlich willkommen im Trickkurs!\n\nBringt bitte Leckerlis und ein Lieblingsspielzeug eures Hundes mit. Wir starten mit einfachen Tricks wie Pfote geben und Drehen.\n\nBis Mittwoch!");
        $n4->setAuthor($manuela ?? $florian);
        $n4->addCourse($courses[6]); // TK Wed
        $manager->persist($n4);

        // Global notification (no courses — visible to everyone)
        $n5 = new Notification();
        $n5->setTitle('Frohe Ostern!');
        $n5->setMessage("Liebe Hundefreunde,\n\nwir wünschen euch und euren Vierbeinern ein wunderschönes Osterfest! 🐣🐕\n\nBitte beachtet: Am Ostermontag finden keine Kurse statt.\n\nEuer Komm!-Team");
        $n5->setAuthor($florian);
        $manager->persist($n5);
    }

    /** @return list<class-string<Fixture>> */
    public function getDependencies(): array
    {
        return [CourseTypeFixtures::class, TrainerFixtures::class];
    }
}
