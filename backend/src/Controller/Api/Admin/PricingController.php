<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\HotelPeakSeason;
use App\Repository\PricingConfigRepository;
use App\Service\ApiNormalizer;
use App\Service\PricingConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/pricing', name: 'api_admin_pricing_')]
#[IsGranted('ROLE_ADMIN')]
final class PricingController extends AbstractController
{
    public function __construct(
        private readonly PricingConfigProvider $pricingConfigProvider,
        private readonly PricingConfigRepository $pricingConfigRepository,
        private readonly ApiNormalizer $normalizer,
    ) {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return $this->json($this->normalizer->normalizePricingConfig($this->pricingConfigProvider->getCurrent()));
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Ungültiger Request Body.'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        $decimalFields = [
            'schoolOneCoursePrice',
            'schoolTwoCoursesUnitPrice',
            'schoolThreeCoursesUnitPrice',
            'schoolFourCoursesUnitPrice',
            'schoolAdditionalCoursesUnitPrice',
            'schoolRegistrationFee',
            'daycareOffSeasonDailyPrice',
            'daycarePeakSeasonDailyPrice',
            'hotelDailyPrice',
            'hotelServiceFee',
            'hotelTravelProtectionBaseFee',
            'hotelTravelProtectionAdditionalDailyFee',
            'hotelSingleRoomDaycareDailyPrice',
            'hotelSingleRoomHotelDailyPrice',
            'hotelHeatCycleDailyPrice',
            'hotelMedicationPerAdministrationPrice',
            'hotelSupplementPerAdministrationPrice',
        ];

        /** @var array<string, string> $normalized */
        $normalized = [];
        foreach ($decimalFields as $field) {
            $value = $payload[$field] ?? null;
            if (!is_scalar($value) || !preg_match('/^-?\d+(?:[.,]\d{1,2})?$/', (string) $value)) {
                $errors[$field] = 'Bitte einen gültigen Preis angeben.';
                continue;
            }

            $normalized[$field] = number_format((float) str_replace(',', '.', (string) $value), 2, '.', '');
        }

        $peakSeasons = $payload['hotelPeakSeasons'] ?? null;
        if (!is_array($peakSeasons) || $peakSeasons === []) {
            $errors['hotelPeakSeasons'] = 'Bitte mindestens eine Hauptsaison angeben.';
        }

        /** @var list<HotelPeakSeason> $seasonEntities */
        $seasonEntities = [];
        if (is_array($peakSeasons)) {
            foreach ($peakSeasons as $index => $seasonPayload) {
                if (!is_array($seasonPayload)) {
                    $errors[sprintf('hotelPeakSeasons.%d', $index)] = 'Ungültige Hauptsaison.';
                    continue;
                }

                $startDateRaw = $seasonPayload['startDate'] ?? null;
                $endDateRaw = $seasonPayload['endDate'] ?? null;
                if (!is_string($startDateRaw) || !is_string($endDateRaw)) {
                    $errors[sprintf('hotelPeakSeasons.%d', $index)] = 'Start- und Enddatum sind erforderlich.';
                    continue;
                }

                try {
                    $startDate = new \DateTimeImmutable($startDateRaw);
                    $endDate = new \DateTimeImmutable($endDateRaw);
                } catch (\Exception) {
                    $errors[sprintf('hotelPeakSeasons.%d', $index)] = 'Ungültiger Zeitraum.';
                    continue;
                }

                if ($endDate < $startDate) {
                    $errors[sprintf('hotelPeakSeasons.%d', $index)] = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
                    continue;
                }

                $season = new HotelPeakSeason();
                $season->setStartDate($startDate);
                $season->setEndDate($endDate);
                $seasonEntities[] = $season;
            }
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $pricingConfig = $this->pricingConfigProvider->getCurrent();
        $pricingConfig
            ->setSchoolOneCoursePrice($normalized['schoolOneCoursePrice'])
            ->setSchoolTwoCoursesUnitPrice($normalized['schoolTwoCoursesUnitPrice'])
            ->setSchoolThreeCoursesUnitPrice($normalized['schoolThreeCoursesUnitPrice'])
            ->setSchoolFourCoursesUnitPrice($normalized['schoolFourCoursesUnitPrice'])
            ->setSchoolAdditionalCoursesUnitPrice($normalized['schoolAdditionalCoursesUnitPrice'])
            ->setSchoolRegistrationFee($normalized['schoolRegistrationFee'])
            ->setDaycareOffSeasonDailyPrice($normalized['daycareOffSeasonDailyPrice'])
            ->setDaycarePeakSeasonDailyPrice($normalized['daycarePeakSeasonDailyPrice'])
            ->setHotelDailyPrice($normalized['hotelDailyPrice'])
            ->setHotelServiceFee($normalized['hotelServiceFee'])
            ->setHotelTravelProtectionBaseFee($normalized['hotelTravelProtectionBaseFee'])
            ->setHotelTravelProtectionAdditionalDailyFee($normalized['hotelTravelProtectionAdditionalDailyFee'])
            ->setHotelSingleRoomDaycareDailyPrice($normalized['hotelSingleRoomDaycareDailyPrice'])
            ->setHotelSingleRoomHotelDailyPrice($normalized['hotelSingleRoomHotelDailyPrice'])
            ->setHotelHeatCycleDailyPrice($normalized['hotelHeatCycleDailyPrice'])
            ->setHotelMedicationPerAdministrationPrice($normalized['hotelMedicationPerAdministrationPrice'])
            ->setHotelSupplementPerAdministrationPrice($normalized['hotelSupplementPerAdministrationPrice'])
            ->touch()
            ->clearHotelPeakSeasons();

        foreach ($seasonEntities as $seasonEntity) {
            $pricingConfig->addHotelPeakSeason($seasonEntity);
        }

        $this->pricingConfigRepository->save($pricingConfig);

        return $this->json($this->normalizer->normalizePricingConfig(
            $this->pricingConfigRepository->findCurrent() ?? $pricingConfig,
        ));
    }
}
