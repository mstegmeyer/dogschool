<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Dto\Pricing\ContractPricingSnapshot;
use App\Dto\Pricing\HotelBookingPricingSnapshot;
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
use App\Service\PricingConfigProvider;
use App\Service\PricingEngine;
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
    // Exported course schedule from Termin_Export_2026-04-05_15_48_53.xlsx
    // -----------------------------------------------------------------------

    /**
     * @var list<array{code: string, date: string, start: string, end: string, level: int, comment?: string}>
     */
    private const COURSE_DEFS = [
        ['code' => 'MH', 'date' => '2026-04-13', 'start' => '08:00', 'end' => '09:00', 'level' => 2],
        ['code' => 'MH', 'date' => '2026-04-13', 'start' => '09:00', 'end' => '10:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-13', 'start' => '10:00', 'end' => '11:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-13', 'start' => '16:00', 'end' => '17:00', 'level' => 2],
        ['code' => 'RO', 'date' => '2026-04-13', 'start' => '17:00', 'end' => '18:00', 'level' => 0],
        ['code' => 'AGI', 'date' => '2026-04-13', 'start' => '18:00', 'end' => '19:00', 'level' => 0],
        ['code' => 'TK', 'date' => '2026-04-13', 'start' => '18:00', 'end' => '19:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-13', 'start' => '19:00', 'end' => '20:00', 'level' => 2],
        ['code' => 'AGI', 'date' => '2026-04-13', 'start' => '19:00', 'end' => '20:00', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-13', 'start' => '20:00', 'end' => '21:00', 'level' => 3],
        ['code' => 'JUHU', 'date' => '2026-04-13', 'start' => '20:00', 'end' => '21:00', 'level' => 2],
        ['code' => 'FSTS', 'date' => '2026-04-13', 'start' => '21:30', 'end' => '23:30', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-14', 'start' => '09:00', 'end' => '10:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-14', 'start' => '16:00', 'end' => '17:00', 'level' => 2],
        ['code' => 'MH', 'date' => '2026-04-14', 'start' => '17:00', 'end' => '18:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-14', 'start' => '17:00', 'end' => '18:00', 'level' => 3],
        ['code' => 'TK', 'date' => '2026-04-14', 'start' => '18:00', 'end' => '19:00', 'level' => 2],
        ['code' => 'TK', 'date' => '2026-04-14', 'start' => '19:00', 'end' => '20:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-14', 'start' => '19:00', 'end' => '20:00', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-14', 'start' => '20:00', 'end' => '21:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-14', 'start' => '20:00', 'end' => '21:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-14', 'start' => '21:00', 'end' => '22:00', 'level' => 4],
        ['code' => 'TK', 'date' => '2026-04-15', 'start' => '17:00', 'end' => '18:00', 'level' => 2],
        ['code' => 'WELPEN', 'date' => '2026-04-15', 'start' => '18:00', 'end' => '19:00', 'level' => 0],
        ['code' => 'TK', 'date' => '2026-04-15', 'start' => '18:00', 'end' => '19:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-15', 'start' => '19:00', 'end' => '20:00', 'level' => 1],
        ['code' => 'TK', 'date' => '2026-04-15', 'start' => '19:00', 'end' => '20:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-15', 'start' => '19:00', 'end' => '20:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-15', 'start' => '20:00', 'end' => '21:00', 'level' => 4],
        ['code' => 'MH', 'date' => '2026-04-15', 'start' => '21:00', 'end' => '22:00', 'level' => 4],
        ['code' => 'FSTS', 'date' => '2026-04-15', 'start' => '22:00', 'end' => '23:30', 'level' => 0],
        ['code' => 'MT', 'date' => '2026-04-16', 'start' => '08:00', 'end' => '09:00', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-16', 'start' => '17:00', 'end' => '18:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-16', 'start' => '17:00', 'end' => '18:00', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-16', 'start' => '18:00', 'end' => '19:00', 'level' => 3],
        ['code' => 'JUHU', 'date' => '2026-04-16', 'start' => '18:00', 'end' => '19:00', 'level' => 1],
        ['code' => 'TK', 'date' => '2026-04-16', 'start' => '19:00', 'end' => '20:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-16', 'start' => '19:00', 'end' => '20:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-16', 'start' => '20:00', 'end' => '21:00', 'level' => 1],
        ['code' => 'AGI', 'date' => '2026-04-16', 'start' => '20:00', 'end' => '21:00', 'level' => 0],
        ['code' => 'FSTS', 'date' => '2026-04-16', 'start' => '21:30', 'end' => '23:30', 'level' => 0],
        ['code' => 'MT', 'date' => '2026-04-17', 'start' => '15:00', 'end' => '16:00', 'level' => 0],
        ['code' => 'JUHU', 'date' => '2026-04-17', 'start' => '16:00', 'end' => '17:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-17', 'start' => '16:00', 'end' => '17:00', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-17', 'start' => '17:00', 'end' => '18:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-17', 'start' => '17:00', 'end' => '18:00', 'level' => 1],
        ['code' => 'AGI', 'date' => '2026-04-17', 'start' => '18:00', 'end' => '19:00', 'level' => 0],
        ['code' => 'JUHU', 'date' => '2026-04-17', 'start' => '18:00', 'end' => '19:00', 'level' => 2],
        ['code' => 'TK', 'date' => '2026-04-17', 'start' => '19:00', 'end' => '19:56', 'level' => 1],
        ['code' => 'FSTS', 'date' => '2026-04-17', 'start' => '20:00', 'end' => '23:59', 'level' => 0],
        ['code' => 'MH', 'date' => '2026-04-18', 'start' => '07:00', 'end' => '08:00', 'level' => 3],
        ['code' => 'MH', 'date' => '2026-04-18', 'start' => '08:00', 'end' => '09:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '08:00', 'end' => '09:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-18', 'start' => '09:00', 'end' => '10:00', 'level' => 3],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '09:00', 'end' => '10:00', 'level' => 3],
        ['code' => 'MH', 'date' => '2026-04-18', 'start' => '10:00', 'end' => '11:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '10:00', 'end' => '11:00', 'level' => 1],
        ['code' => 'MH', 'date' => '2026-04-18', 'start' => '11:00', 'end' => '12:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '11:00', 'end' => '12:00', 'level' => 2],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '11:00', 'end' => '12:00', 'level' => 1],
        ['code' => 'WELPEN', 'date' => '2026-04-18', 'start' => '12:00', 'end' => '13:00', 'level' => 0],
        ['code' => 'TK', 'date' => '2026-04-18', 'start' => '12:00', 'end' => '13:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '13:00', 'end' => '14:00', 'level' => 1],
        ['code' => 'JUHU', 'date' => '2026-04-18', 'start' => '14:00', 'end' => '15:00', 'level' => 0],
        ['code' => 'JUHU', 'date' => '2026-04-19', 'start' => '18:00', 'end' => '19:00', 'level' => 1, 'comment' => 'Nachholkurs'],
        ['code' => 'TK', 'date' => '2026-04-19', 'start' => '19:00', 'end' => '20:00', 'level' => 1, 'comment' => 'Nachholkurs'],
        ['code' => 'MH', 'date' => '2026-04-19', 'start' => '20:00', 'end' => '21:00', 'level' => 1, 'comment' => 'Nachholkurs'],
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
        $this->createPricingConfig($manager);
        $courseTypes = $this->indexCourseTypes($manager);
        $trainers = $this->indexTrainers($manager);

        $customers = $this->createCustomers($manager);
        $courses = $this->createCourses($manager, $courseTypes, $trainers);
        $courseDates = $this->createCourseDates($manager, $courses);
        $this->createSubscriptions($manager, $customers, $courses);
        $this->createContracts($manager, $customers);
        $this->createCreditTransactions($manager);
        $this->createBookings($manager, $customers, $courseDates);
        $this->createHotelData($manager, $customers);
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
                $dog->setShoulderHeightCm(self::defaultDogShoulderHeight($dogIdx));
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
    // Courses from the exported training week
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
        foreach (self::COURSE_DEFS as $definition) {
            $typeCode = $definition['code'];
            if (!isset($courseTypes[$typeCode])) {
                continue;
            }

            $courseDate = new \DateTimeImmutable($definition['date']);
            $c = new Course();
            $c->setCourseType($courseTypes[$typeCode]);
            $c->setDayOfWeek((int) $courseDate->format('N'));
            $c->setStartTime($definition['start']);
            $c->setEndTime($definition['end']);
            $c->setLevel($definition['level']);
            $c->setComment($definition['comment'] ?? null);
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
    // Exact course dates from the exported training week
    // -----------------------------------------------------------------------

    /**
     * @param Course[] $courses
     *
     * @return array<int, CourseDate[]> keyed by course index
     */
    private function createCourseDates(ObjectManager $manager, array $courses): array
    {
        $allDates = [];
        foreach ($courses as $idx => $course) {
            $definition = self::COURSE_DEFS[$idx] ?? null;
            if ($definition === null) {
                continue;
            }

            $cd = new CourseDate();
            $cd->setCourse($course);
            $cd->setTrainer($course->getTrainer());
            $cd->setDate(new \DateTimeImmutable($definition['date']));
            $cd->setStartTime($definition['start']);
            $cd->setEndTime($definition['end']);
            $manager->persist($cd);
            $allDates[$idx] = [$cd];
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
    // Contracts (40 ACTIVE, 1 PENDING REVIEW, 4 REQUESTED, 3 DECLINED, 2 CANCELLED)
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
                $i === 40 => ContractState::PENDING_CUSTOMER_APPROVAL,
                $i < 45 => ContractState::REQUESTED,
                $i < 48 => ContractState::DECLINED,
                default => ContractState::CANCELLED,
            };

            $coursesPerWeek = 1 + ($i % 3);

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
        \DateTimeImmutable $startDate,
    ): Contract {
        $contract = new Contract();
        $contract->setCustomer($customer);
        $contract->setDog($dog);
        $contract->setState($state);
        $contract->setCoursesPerWeek($coursesPerWeek);
        $contract->setStartDate($startDate);
        $contract->setEndDate($startDate->modify('+1 year'));
        $contract->setType(ContractType::PERPETUAL);
        $contract->setCustomerComment('Bitte Trainingszeiten flexibel halten.');
        $this->applyContractPricing($contract, $state === ContractState::PENDING_CUSTOMER_APPROVAL ? 24_00 : 0, true);
        if ($state === ContractState::PENDING_CUSTOMER_APPROVAL) {
            $contract->setAdminComment('Preis angepasst wegen zusätzlicher Einzelbetreuung.');
        }

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
                $this->book($manager, $entry['customer'], $dog, $courseDates[$courseIdx][0] ?? null);
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
    // Hotel rooms + bookings
    // -----------------------------------------------------------------------

    /**
     * @param array<int, array{customer: Customer, dogs: Dog[]}> $customers
     */
    private function createHotelData(ObjectManager $manager, array $customers): void
    {
        $hotelDogEntries = $this->buildHotelDogEntries($customers);
        $rooms = [];
        foreach ([
            'waldzimmer' => ['name' => 'Waldzimmer', 'squareMeters' => 10, 'pattern' => 'compact'],
            'birkenkoje' => ['name' => 'Birkenkoje', 'squareMeters' => 12, 'pattern' => 'compact'],
            'wiesennest' => ['name' => 'Wiesennest', 'squareMeters' => 12, 'pattern' => 'compact'],
            'gartenkabine' => ['name' => 'Gartenkabine', 'squareMeters' => 14, 'pattern' => 'compact'],
            'sonnenstube' => ['name' => 'Sonnenstube', 'squareMeters' => 16, 'pattern' => 'paired'],
            'parksuite' => ['name' => 'Parksuite', 'squareMeters' => 16, 'pattern' => 'paired'],
            'landhaus' => ['name' => 'Landhaus', 'squareMeters' => 18, 'pattern' => 'paired'],
            'hofblick' => ['name' => 'Hofblick', 'squareMeters' => 18, 'pattern' => 'paired'],
            'apfelhof' => ['name' => 'Apfelhof', 'squareMeters' => 20, 'pattern' => 'busy'],
            'muehlenloft' => ['name' => 'Mühlenloft', 'squareMeters' => 20, 'pattern' => 'busy'],
            'kaminzimmer' => ['name' => 'Kaminzimmer', 'squareMeters' => 22, 'pattern' => 'busy'],
            'pfoetchenloft' => ['name' => 'Pfötchenloft', 'squareMeters' => 24, 'pattern' => 'busy'],
        ] as $key => $definition) {
            $room = new Room();
            $room->setName($definition['name']);
            $room->setSquareMeters($definition['squareMeters']);
            $manager->persist($room);
            $rooms[$key] = ['room' => $room, 'pattern' => $definition['pattern']];
        }

        $today = new \DateTimeImmutable('today 00:00');
        $timeframes = [
            'overnight_departing' => [
                'start' => $today->modify('-1 day')->setTime(18, 0),
                'end' => $today->setTime(7, 30),
            ],
            'today_arrival' => [
                'start' => $today->setTime(9, 30),
                'end' => $today->modify('+1 day')->setTime(8, 30),
            ],
            'today_overlap' => [
                'start' => $today->setTime(13, 30),
                'end' => $today->modify('+1 day')->setTime(17, 30),
            ],
            'tomorrow_evening' => [
                'start' => $today->modify('+1 day')->setTime(18, 30),
                'end' => $today->modify('+2 days')->setTime(12, 0),
            ],
            'day_three_overlap' => [
                'start' => $today->modify('+2 days')->setTime(9, 30),
                'end' => $today->modify('+3 days')->setTime(17, 30),
            ],
            'weekend' => [
                'start' => $today->modify('+4 days')->setTime(8, 0),
                'end' => $today->modify('+5 days')->setTime(18, 0),
            ],
            'requested_short' => [
                'start' => $today->modify('+1 day')->setTime(6, 30),
                'end' => $today->modify('+1 day')->setTime(17, 0),
            ],
            'requested_multi_day' => [
                'start' => $today->modify('+2 days')->setTime(8, 0),
                'end' => $today->modify('+4 days')->setTime(10, 0),
            ],
            'requested_daycare' => [
                'start' => $today->modify('+3 days')->setTime(9, 30),
                'end' => $today->modify('+3 days')->setTime(19, 30),
            ],
            'requested_weekend' => [
                'start' => $today->modify('+5 days')->setTime(7, 0),
                'end' => $today->modify('+6 days')->setTime(18, 30),
            ],
            'requested_extended' => [
                'start' => $today->modify('+6 days')->setTime(10, 0),
                'end' => $today->modify('+8 days')->setTime(16, 0),
            ],
            'requested_morning' => [
                'start' => $today->modify('+7 days')->setTime(6, 0),
                'end' => $today->modify('+7 days')->setTime(15, 0),
            ],
            'requested_evening' => [
                'start' => $today->modify('+8 days')->setTime(18, 0),
                'end' => $today->modify('+9 days')->setTime(11, 0),
            ],
            'requested_long' => [
                'start' => $today->modify('+9 days')->setTime(9, 0),
                'end' => $today->modify('+11 days')->setTime(13, 0),
            ],
            'declined_one' => [
                'start' => $today->modify('+2 days')->setTime(8, 30),
                'end' => $today->modify('+3 days')->setTime(17, 0),
            ],
            'declined_two' => [
                'start' => $today->modify('+4 days')->setTime(10, 0),
                'end' => $today->modify('+5 days')->setTime(18, 0),
            ],
            'declined_three' => [
                'start' => $today->modify('+6 days')->setTime(7, 30),
                'end' => $today->modify('+7 days')->setTime(14, 0),
            ],
            'declined_four' => [
                'start' => $today->modify('+8 days')->setTime(12, 0),
                'end' => $today->modify('+9 days')->setTime(18, 0),
            ],
        ];

        $roomIndex = 0;
        foreach ($rooms as $entry) {
            $room = $entry['room'];
            $pattern = $entry['pattern'];
            $slotKeys = match ($pattern) {
                'compact' => ['overnight_departing', 'today_arrival', 'tomorrow_evening', 'weekend'],
                'paired' => ['overnight_departing', 'today_arrival', 'today_overlap', 'weekend'],
                default => ['overnight_departing', 'today_arrival', 'today_overlap', 'tomorrow_evening', 'day_three_overlap'],
            };

            foreach ($slotKeys as $slotIndex => $slotKey) {
                $dogEntry = $this->shiftHotelDogEntry($hotelDogEntries);
                $window = $this->varyHotelWindow(
                    $timeframes[$slotKey]['start'],
                    $timeframes[$slotKey]['end'],
                    $roomIndex,
                    $slotIndex,
                );
                $this->createHotelBooking(
                    $manager,
                    $dogEntry['customer'],
                    $dogEntry['dog'],
                    $room,
                    HotelBookingState::CONFIRMED,
                    $window['start'],
                    $window['end'],
                );
            }

            ++$roomIndex;
        }

        foreach ([
            'requested_short',
            'requested_multi_day',
            'requested_daycare',
            'requested_weekend',
            'requested_extended',
            'requested_morning',
            'requested_evening',
            'requested_long',
        ] as $slotIndex => $slotKey) {
            $travelProtection = in_array($slotKey, ['requested_multi_day', 'requested_extended', 'requested_long'], true);
            $window = $this->varyHotelWindow(
                $timeframes[$slotKey]['start'],
                $timeframes[$slotKey]['end'],
                20,
                $slotIndex,
            );
            $dogEntry = $this->shiftHotelDogEntry($hotelDogEntries);
            $this->createHotelBooking(
                $manager,
                $dogEntry['customer'],
                $dogEntry['dog'],
                null,
                HotelBookingState::REQUESTED,
                $window['start'],
                $window['end'],
                $travelProtection,
            );
        }

        $dogEntry = $this->shiftHotelDogEntry($hotelDogEntries);
        $this->createHotelBooking(
            $manager,
            $dogEntry['customer'],
            $dogEntry['dog'],
            $rooms['apfelhof']['room'],
            HotelBookingState::PENDING_CUSTOMER_APPROVAL,
            $today->modify('+1 day')->setTime(8, 30),
            $today->modify('+2 days')->setTime(10, 0),
            true,
            25_00,
        );

        foreach ([
            'declined_one',
            'declined_two',
            'declined_three',
            'declined_four',
        ] as $slotIndex => $slotKey) {
            $dogEntry = $this->shiftHotelDogEntry($hotelDogEntries);
            $window = $this->varyHotelWindow(
                $timeframes[$slotKey]['start'],
                $timeframes[$slotKey]['end'],
                40,
                $slotIndex,
            );
            $this->createHotelBooking(
                $manager,
                $dogEntry['customer'],
                $dogEntry['dog'],
                null,
                HotelBookingState::DECLINED,
                $window['start'],
                $window['end'],
                false,
            );
        }
    }

    private function createHotelBooking(
        ObjectManager $manager,
        Customer $customer,
        Dog $dog,
        ?Room $room,
        HotelBookingState $state,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        bool $includesTravelProtection = false,
        int $extraPriceCents = 0,
    ): void {
        $booking = new HotelBooking();
        $booking->setCustomer($customer);
        $booking->setDog($dog);
        $booking->setRoom($room);
        $booking->setState($state);
        $booking->setStartAt($startAt);
        $booking->setEndAt($endAt);
        $booking->setIncludesTravelProtection($includesTravelProtection);
        $booking->setCustomerComment('Bitte möglichst ruhige Unterbringung.');
        $this->applyHotelPricing($booking, $extraPriceCents);
        if ($state === HotelBookingState::PENDING_CUSTOMER_APPROVAL) {
            $booking->setAdminComment('Preis angepasst wegen manueller Zusatzwünsche.');
        }
        $manager->persist($booking);
    }

    /**
     * Spread bookings across the day so arrivals and departures look less synchronized.
     *
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}
     */
    private function varyHotelWindow(
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        int $groupIndex,
        int $slotIndex,
    ): array {
        $startShiftMinutes = (($groupIndex * 43) + ($slotIndex * 37)) % 150 - 40;
        $endShiftMinutes = (($groupIndex * 29) + ($slotIndex * 31)) % 120 - 20;

        $shiftedStart = $this->normalizeHotelBoundary($startAt->modify(sprintf('%+d minutes', $startShiftMinutes)));
        $shiftedEnd = $this->normalizeHotelBoundary($endAt->modify(sprintf('%+d minutes', $startShiftMinutes + $endShiftMinutes)));

        if ($shiftedEnd <= $shiftedStart) {
            $minimumDurationMinutes = max(240, (int) (($endAt->getTimestamp() - $startAt->getTimestamp()) / 120));
            $shiftedEnd = $this->normalizeHotelBoundary($shiftedStart->modify(sprintf('+%d minutes', $minimumDurationMinutes)));
        }

        if ($shiftedEnd <= $shiftedStart) {
            $shiftedEnd = $shiftedStart->modify('+1 day')->setTime(10, 0);
        }

        return [
            'start' => $shiftedStart,
            'end' => $shiftedEnd,
        ];
    }

    private function normalizeHotelBoundary(\DateTimeImmutable $value): \DateTimeImmutable
    {
        $minutes = ((int) $value->format('H') * 60) + (int) $value->format('i');

        if ($minutes < 360) {
            return $value->setTime(6, 0);
        }

        if ($minutes > 1320) {
            return $value->setTime(21, 45);
        }

        return $value;
    }

    private function createPricingConfig(ObjectManager $manager): void
    {
        if ($manager->getRepository(PricingConfig::class)->findOneBy([]) instanceof PricingConfig) {
            return;
        }

        $manager->persist($this->createDefaultPricingConfigEntity());
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
        $contract->setPricingSnapshot(
            ContractPricingSnapshot::forQuote(
                $contract->getCoursesPerWeek(),
                PricingEngine::schoolUnitPriceForCourseCount(new PricingConfig(), $contract->getCoursesPerWeek()),
                $quotedMonthlyPrice,
                $registrationFee,
                PricingEngine::formatAmount(
                    PricingEngine::amountToCents($quotedMonthlyPrice) + PricingEngine::amountToCents($registrationFee),
                ),
            )
                ->finalize($finalMonthlyPrice, $registrationFee)
                ->toArray(),
        );
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
        $booking->setPricingSnapshot(
            HotelBookingPricingSnapshot::forQuote(
                $pricingKind,
                $billableDays,
                PricingEngine::formatAmount($baseDailyPriceCents),
                PricingEngine::formatAmount($serviceFeeCents),
                PricingEngine::formatAmount($travelProtectionCents),
                PricingEngine::formatAmount($quotedTotalCents),
                $pricingKind === HotelBookingPricingKind::DAYCARE
                    ? sprintf('HUTA %s', $this->isPeakSeasonDate($booking->getStartAt()) ? 'Hauptsaison' : 'Nebensaison')
                    : 'Hundehotel',
                $booking->includesTravelProtection(),
            )
                ->finalize($booking->getTotalPrice())
                ->toArray(),
        );
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

    /**
     * @param array<int, array{customer: Customer, dogs: Dog[]}> $customers
     *
     * @return list<array{customer: Customer, dog: Dog}>
     */
    private function buildHotelDogEntries(array $customers): array
    {
        $entries = [];

        foreach ($customers as $entry) {
            foreach ($entry['dogs'] as $dog) {
                $entries[] = [
                    'customer' => $entry['customer'],
                    'dog' => $dog,
                ];
            }
        }

        return $entries;
    }

    /**
     * @param list<array{customer: Customer, dog: Dog}> $hotelDogEntries
     *
     * @return array{customer: Customer, dog: Dog}
     */
    private function shiftHotelDogEntry(array &$hotelDogEntries): array
    {
        $entry = array_shift($hotelDogEntries);
        if ($entry === null) {
            throw new \LogicException('Not enough dogs available for hotel demo fixtures.');
        }

        return $entry;
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

        // Sunday make-up classes
        $n = new Notification();
        $n->setTitle('Nachholkurse am Sonntag');
        $n->setMessage("Liebe Kursteilnehmer,\n\nam Sonntag bieten wir drei zusätzliche Nachholkurse an: JUHU 1 um 18:00 Uhr, TK 1 um 19:00 Uhr und MH 1 um 20:00 Uhr. Bitte bucht euch wie gewohnt über den Kalender ein.\n\nViele Grüße,\nFlorian");
        $n->setAuthor($florian);
        $this->addCoursesByComment($n, $courses, 'Nachholkurs');
        $manager->persist($n);

        // All AGI: Parcours erneuert
        $n = new Notification();
        $n->setTitle('Agility-Parcours erneuert');
        $n->setMessage("Hallo zusammen,\n\nwir haben neue Hindernisse für den Agility-Parcours bekommen! Tunnel, Wippe und Slalom sind alle brandneu. Freut euch auf das nächste Training!\n\nEuer Komm!-Team");
        $n->setAuthor($manuela ?? $florian);
        $this->addCoursesByType($n, $courses, ['AGI']);
        $manager->persist($n);

        // JUHU + TK: Sommerpause-Info
        $n = new Notification();
        $n->setTitle('Sommerpause-Info');
        $n->setMessage("Liebe Hundefreunde,\n\nin der letzten Juli-Woche und den ersten zwei August-Wochen findet kein regulärer Kurs statt. Das Hundehotel bleibt geöffnet.\n\nSchöne Grüße,\nEuer Komm!-Team");
        $n->setAuthor($florian);
        $this->addCoursesByType($n, $courses, ['JUHU', 'TK']);
        $manager->persist($n);

        // All TK: Willkommen
        $n = new Notification();
        $n->setTitle('Willkommen im Teamkurs!');
        $n->setMessage("Hallo und herzlich willkommen im Teamkurs!\n\nBringt bitte Leckerlis und ein Lieblingsspielzeug eures Hundes mit. Wir starten mit lockeren Kooperationsübungen und kleinen Aufgaben für den Alltag.\n\nBis bald!");
        $n->setAuthor($manuela ?? $florian);
        $this->addCoursesByType($n, $courses, ['TK']);
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
        $this->addCoursesByType($n, $courses, ['MT']);
        $manager->persist($n);

        // All WELPEN: Erste Stunde
        $n = new Notification();
        $n->setTitle('Welpengruppe: Bitte Decke mitbringen');
        $n->setMessage("Hallo zusammen,\n\nfür die Welpengruppe bringt bitte eine kleine Decke, weiche Leckerchen und gern ein ruhiges Lieblingsspielzeug mit. So können wir die erste Stunde entspannt aufbauen.\n\nLiebe Grüße,\nLea");
        $n->setAuthor($lea ?? $florian);
        $this->addCoursesByType($n, $courses, ['WELPEN']);
        $manager->persist($n);

        // All AGI pinned: Turnier-Ankündigung
        $n = new Notification();
        $n->setTitle('Agility-Turnier am 12. April');
        $n->setMessage("Liebe Agility-Teilnehmer,\n\nam 12. April findet unser vereinsinternes Agility-Turnier statt! Anmeldungen bitte bis zum 10. April bei Manuela. Es gibt Pokale in allen Leistungsklassen.\n\nStart: 10:00 Uhr\nOrt: Trainingsgelände\n\nWir freuen uns auf euch!");
        $n->setAuthor($manuela ?? $florian);
        $this->addCoursesByType($n, $courses, ['AGI']);
        $n->setPinnedUntil(new \DateTimeImmutable('2026-04-12T23:59:59'));
        $manager->persist($n);

        // All FSTS: Abendtraining
        $n = new Notification();
        $n->setTitle('Abendtraining: Stirnlampe mitbringen');
        $n->setMessage("Hallo zusammen,\n\ndie späteren FS/TS-Einheiten finden diese Woche teilweise in der Dämmerung statt. Bringt bitte eine Stirnlampe und wetterfeste Kleidung mit.\n\nViele Grüße,\nCaro");
        $n->setAuthor($caro ?? $florian);
        $this->addCoursesByType($n, $courses, ['FSTS']);
        $manager->persist($n);

        // All JUHU pinned: Schnuppertag
        $n = new Notification();
        $n->setTitle('Junghunde-Schnuppertag am 12. April');
        $n->setMessage("Liebe Junghunde-Besitzer,\n\nam 12. April laden wir zum kostenlosen Schnuppertag ein! Bringt gerne Freunde mit Junghunden bis 12 Monate mit. Wir zeigen Grundübungen und beantworten eure Fragen.\n\nAnmeldung per E-Mail genügt.\n\nEuer Komm!-Team");
        $n->setAuthor($florian);
        $this->addCoursesByType($n, $courses, ['JUHU']);
        $n->setPinnedUntil(new \DateTimeImmutable('2026-04-12T23:59:59'));
        $manager->persist($n);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * @param Course[]     $courses
     * @param list<string> $codes
     */
    private function addCoursesByType(Notification $notification, array $courses, array $codes): void
    {
        foreach ($courses as $course) {
            $courseTypeCode = $course->getCourseType()?->getCode();
            if ($courseTypeCode !== null && in_array($courseTypeCode, $codes, true)) {
                $notification->addCourse($course);
            }
        }
    }

    /**
     * @param Course[] $courses
     */
    private function addCoursesByComment(Notification $notification, array $courses, string $comment): void
    {
        foreach ($courses as $course) {
            if ($course->getComment() === $comment) {
                $notification->addCourse($course);
            }
        }
    }

    private static function nameToEmail(string $name): string
    {
        $lower = mb_strtolower($name, 'UTF-8');

        return str_replace(
            [' ', 'ä', 'ö', 'ü', 'ß'],
            ['.', 'ae', 'oe', 'ue', 'ss'],
            $lower,
        ).'@example.com';
    }

    private static function defaultDogShoulderHeight(int $index): int
    {
        $heights = [34, 40, 46, 50, 54, 58, 62, 68];

        return $heights[$index % count($heights)];
    }

    /** @return list<class-string<Fixture>> */
    public function getDependencies(): array
    {
        return [CourseTypeFixtures::class, TrainerFixtures::class];
    }
}
