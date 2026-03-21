<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Customer;

use App\Entity\Course;
use App\Entity\CourseType;
use App\Repository\CourseRepository;
use App\Repository\CourseTypeRepository;
use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CourseControllerTest extends WebTestCase
{
    public function testListCoursesRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/courses');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSubscribedRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/api/customer/courses/subscribed');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSubscribeAndUnsubscribe(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token, 'customer' => $customer] = $helper->createCustomerAndLogin();

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
        $course->setDayOfWeek(1);
        $course->setStartTime('10:00');
        $course->setEndTime('11:00');
        $course->setCourseType($courseType);
        $course->setLevel(0);
        $course->setArchived(false);
        $courseRepo->save($course);

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/subscribed', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(0, $data['items']);

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/courses/'.$course->getId().'/subscribe', $token);
        self::assertResponseIsSuccessful();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/subscribed', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(1, $data['items']);
        self::assertSame($course->getId(), $data['items'][0]['id']);

        $helper->customerRequest(Request::METHOD_DELETE, '/api/customer/courses/'.$course->getId().'/subscribe', $token);
        self::assertResponseIsSuccessful();

        $helper->customerRequest(Request::METHOD_GET, '/api/customer/courses/subscribed', $token);
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertCount(0, $data['items']);
    }

    public function testSubscribeToNonExistentCourseReturns404(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createCustomerAndLogin();

        $helper->customerRequest(Request::METHOD_POST, '/api/customer/courses/00000000-0000-0000-0000-000000000000/subscribe', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
