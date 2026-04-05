<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api\Admin;

use App\Tests\Helper\ApiTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PricingControllerTest extends WebTestCase
{
    public function testAdminCanGetAndUpdatePricingConfig(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        try {
            $helper->adminRequest(Request::METHOD_GET, '/api/admin/pricing', $token);
            self::assertResponseIsSuccessful();
            $current = json_decode($client->getResponse()->getContent() ?: '{}', true);
            self::assertSame('89.00', $current['schoolOneCoursePrice'] ?? null);
            self::assertNotEmpty($current['hotelPeakSeasons'] ?? []);

            $helper->adminRequest(Request::METHOD_PUT, '/api/admin/pricing', $token, json_encode([
                'schoolOneCoursePrice' => '91.00',
                'schoolTwoCoursesUnitPrice' => '82.00',
                'schoolThreeCoursesUnitPrice' => '78.00',
                'schoolFourCoursesUnitPrice' => '73.00',
                'schoolAdditionalCoursesUnitPrice' => '69.00',
                'schoolRegistrationFee' => '159.00',
                'daycareOffSeasonDailyPrice' => '40.00',
                'daycarePeakSeasonDailyPrice' => '47.00',
                'hotelDailyPrice' => '60.00',
                'hotelServiceFee' => '8.50',
                'hotelTravelProtectionBaseFee' => '50.00',
                'hotelTravelProtectionAdditionalDailyFee' => '12.00',
                'hotelSingleRoomDaycareDailyPrice' => '21.00',
                'hotelSingleRoomHotelDailyPrice' => '30.00',
                'hotelHeatCycleDailyPrice' => '7.00',
                'hotelMedicationPerAdministrationPrice' => '4.00',
                'hotelSupplementPerAdministrationPrice' => '4.50',
                'hotelPeakSeasons' => [
                    ['startDate' => '2026-07-01', 'endDate' => '2026-08-31'],
                ],
            ]));
            self::assertResponseIsSuccessful();

            $updated = json_decode($client->getResponse()->getContent() ?: '{}', true);
            self::assertSame('91.00', $updated['schoolOneCoursePrice'] ?? null);
            self::assertSame('8.50', $updated['hotelServiceFee'] ?? null);
            self::assertCount(1, $updated['hotelPeakSeasons'] ?? []);
        } finally {
            $helper->adminRequest(Request::METHOD_PUT, '/api/admin/pricing', $token, json_encode([
                'schoolOneCoursePrice' => '89.00',
                'schoolTwoCoursesUnitPrice' => '80.00',
                'schoolThreeCoursesUnitPrice' => '76.00',
                'schoolFourCoursesUnitPrice' => '71.00',
                'schoolAdditionalCoursesUnitPrice' => '67.00',
                'schoolRegistrationFee' => '149.00',
                'daycareOffSeasonDailyPrice' => '39.00',
                'daycarePeakSeasonDailyPrice' => '46.00',
                'hotelDailyPrice' => '58.00',
                'hotelServiceFee' => '7.50',
                'hotelTravelProtectionBaseFee' => '49.00',
                'hotelTravelProtectionAdditionalDailyFee' => '11.00',
                'hotelSingleRoomDaycareDailyPrice' => '20.00',
                'hotelSingleRoomHotelDailyPrice' => '29.00',
                'hotelHeatCycleDailyPrice' => '6.00',
                'hotelMedicationPerAdministrationPrice' => '3.50',
                'hotelSupplementPerAdministrationPrice' => '3.50',
                'hotelPeakSeasons' => [
                    ['startDate' => '2026-01-01', 'endDate' => '2026-01-11'],
                    ['startDate' => '2026-03-27', 'endDate' => '2026-04-14'],
                    ['startDate' => '2026-06-29', 'endDate' => '2026-09-13'],
                    ['startDate' => '2026-10-16', 'endDate' => '2026-11-02'],
                    ['startDate' => '2026-12-21', 'endDate' => '2027-01-10'],
                ],
            ]));
            self::assertResponseIsSuccessful();
        }
    }

    public function testUpdateRejectsNegativePrices(): void
    {
        $client = static::createClient();
        $helper = ApiTestHelper::create($client);
        ['token' => $token] = $helper->createAdminAndLogin();

        $helper->adminRequest(Request::METHOD_PUT, '/api/admin/pricing', $token, json_encode([
            'schoolOneCoursePrice' => '-1.00',
            'schoolTwoCoursesUnitPrice' => '80.00',
            'schoolThreeCoursesUnitPrice' => '76.00',
            'schoolFourCoursesUnitPrice' => '71.00',
            'schoolAdditionalCoursesUnitPrice' => '67.00',
            'schoolRegistrationFee' => '149.00',
            'daycareOffSeasonDailyPrice' => '39.00',
            'daycarePeakSeasonDailyPrice' => '46.00',
            'hotelDailyPrice' => '58.00',
            'hotelServiceFee' => '7.50',
            'hotelTravelProtectionBaseFee' => '49.00',
            'hotelTravelProtectionAdditionalDailyFee' => '11.00',
            'hotelSingleRoomDaycareDailyPrice' => '20.00',
            'hotelSingleRoomHotelDailyPrice' => '29.00',
            'hotelHeatCycleDailyPrice' => '6.00',
            'hotelMedicationPerAdministrationPrice' => '3.50',
            'hotelSupplementPerAdministrationPrice' => '3.50',
            'hotelPeakSeasons' => [
                ['startDate' => '2026-01-01', 'endDate' => '2026-01-11'],
            ],
        ]));
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent() ?: '{}', true);
        self::assertSame('Bitte einen gültigen Preis angeben.', $data['errors']['schoolOneCoursePrice'] ?? null);
    }
}
