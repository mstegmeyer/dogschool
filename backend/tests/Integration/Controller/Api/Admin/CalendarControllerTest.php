<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Entity\Course;
use App\Entity\CourseDate;
use App\Entity\CourseType;
use App\Entity\User;
use App\Repository\CourseDateRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Repository\UserRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CalendarControllerTest extends WebTestCase
{
    private function seedCourseDate(string $dateStr, ?User $trainer = null): array
    {
        $container = static::getContainer();
        $courseTypeRepo = $container->get(CourseTypeRepository::class);
        $courseType = $courseTypeRepo->findByCode('JUHU');
        if ($courseType === null) {
            $courseType = new CourseType();
            $courseType->setCode('JUHU');
            $courseType->setName('Junghunde');
            $courseTypeRepo->save($courseType);
        }

        $courseRepo = $container->get(CourseRepository::class);
        $course = new Course();
        $course->setDayOfWeek((int) (new \DateTimeImmutable($dateStr))->format('N'));
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);
        $course->setTrainer($trainer);
        $courseRepo->save($course);

        $cdRepo = $container->get(CourseDateRepository::class);
        $cd = new CourseDate();
        $cd->setCourse($course);
        $cd->setTrainer($trainer);
        $cd->setDate(new \DateTimeImmutable($dateStr));
        $cd->setStartTime('10:00');
        $cd->setEndTime('11:00');
        $cdRepo->save($cd);

        return ['courseDate' => $cd, 'course' => $course];
    }

    private function reloadUser(User $user): User
    {
        $reloaded = static::getContainer()->get(UserRepository::class)->find($user->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }

    public function testListCalendarRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/admin/calendar');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListCalendarReturnsCourseDates(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $nextMonday = (new \DateTimeImmutable('monday this week'))->modify('+7 days');
        $this->seedCourseDate($nextMonday->format('Y-m-d'));

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/calendar?week='.$nextMonday->format('Y-m-d'), $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('from', $data);
        self::assertArrayHasKey('to', $data);
    }

    public function testListCalendarByMonth(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/calendar?month=2026-04', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('2026-04-01', $data['from']);
    }

    public function testGetCourseDateById(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $seeded = $this->seedCourseDate((new \DateTimeImmutable('+3 days'))->format('Y-m-d'));
        $cdId = $seeded['courseDate']->getId();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/calendar/course-dates/'.$cdId, $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($cdId, $data['id']);
    }

    public function testGetCourseDateReturns404(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_GET, '/api/admin/calendar/course-dates/00000000-0000-0000-0000-000000000000', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testMoveCourseDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $seeded = $this->seedCourseDate((new \DateTimeImmutable('+5 days'))->format('Y-m-d'));
        $cdId = $seeded['courseDate']->getId();
        $newDate = (new \DateTimeImmutable('+10 days'))->format('Y-m-d');

        $helper->adminRequest(Request::METHOD_PUT, '/api/admin/calendar/course-dates/'.$cdId.'/move', $token, json_encode([
            'date' => $newDate,
            'startTime' => '14:00',
            'endTime' => '15:00',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame($newDate, $data['date']);
        self::assertSame('14:00', $data['startTime']);
    }

    public function testCancelAndUncancelCourseDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $seeded = $this->seedCourseDate((new \DateTimeImmutable('+4 days'))->format('Y-m-d'));
        $cdId = $seeded['courseDate']->getId();

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/calendar/course-dates/'.$cdId.'/cancel', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertTrue($data['cancelled']);

        $helper->adminRequest(Request::METHOD_POST, '/api/admin/calendar/course-dates/'.$cdId.'/uncancel', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertFalse($data['cancelled']);
    }

    public function testUpdateTrainerForSingleCourseDate(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();
        ['user' => $defaultTrainer] = $helper->createAdminAndLogin(fullName: 'Default Trainer');
        ['user' => $replacementTrainer] = $helper->createAdminAndLogin(fullName: 'Replacement Trainer');
        $defaultTrainer = $this->reloadUser($defaultTrainer);
        $replacementTrainer = $this->reloadUser($replacementTrainer);

        $seeded = $this->seedCourseDate((new \DateTimeImmutable('+5 days'))->format('Y-m-d'), $defaultTrainer);
        $cdId = $seeded['courseDate']->getId();

        $helper->adminRequest(
            Request::METHOD_PUT,
            '/api/admin/calendar/course-dates/'.$cdId.'/trainer',
            $token,
            json_encode(['trainerId' => $replacementTrainer->getId()])
        );
        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Replacement Trainer', $data['trainer']['fullName']);
        self::assertTrue($data['trainerOverridden']);

        $courseDateRepo = static::getContainer()->get(CourseDateRepository::class);
        $reloadedCourseDate = $courseDateRepo->find($cdId);
        self::assertNotNull($reloadedCourseDate);
        self::assertSame($replacementTrainer->getId(), $reloadedCourseDate->getTrainer()?->getId());
    }
}
