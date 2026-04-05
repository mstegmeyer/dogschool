<?php

declare(strict_types=1);

namespace App\Entity;

use App\Support\AppClock;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: \App\Repository\PricingConfigRepository::class)]
#[ORM\Table(name: 'pricing_config')]
class PricingConfig
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $schoolOneCoursePrice = '89.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $schoolTwoCoursesUnitPrice = '80.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $schoolThreeCoursesUnitPrice = '76.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $schoolFourCoursesUnitPrice = '71.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $schoolAdditionalCoursesUnitPrice = '67.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $schoolRegistrationFee = '149.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $daycareOffSeasonDailyPrice = '39.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $daycarePeakSeasonDailyPrice = '46.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelDailyPrice = '58.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelServiceFee = '7.50';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelTravelProtectionBaseFee = '49.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelTravelProtectionAdditionalDailyFee = '11.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelSingleRoomDaycareDailyPrice = '20.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelSingleRoomHotelDailyPrice = '29.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelHeatCycleDailyPrice = '6.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelMedicationPerAdministrationPrice = '3.50';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hotelSupplementPerAdministrationPrice = '3.50';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, HotelPeakSeason> */
    #[ORM\OneToMany(targetEntity: HotelPeakSeason::class, mappedBy: 'pricingConfig', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['startDate' => 'ASC', 'endDate' => 'ASC'])]
    private Collection $hotelPeakSeasons;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = AppClock::now();
        $this->updatedAt = $this->createdAt;
        $this->hotelPeakSeasons = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSchoolOneCoursePrice(): string
    {
        return self::normalizeAmount($this->schoolOneCoursePrice);
    }

    public function setSchoolOneCoursePrice(string $schoolOneCoursePrice): static
    {
        $this->schoolOneCoursePrice = self::normalizeAmount($schoolOneCoursePrice);

        return $this;
    }

    public function getSchoolTwoCoursesUnitPrice(): string
    {
        return self::normalizeAmount($this->schoolTwoCoursesUnitPrice);
    }

    public function setSchoolTwoCoursesUnitPrice(string $schoolTwoCoursesUnitPrice): static
    {
        $this->schoolTwoCoursesUnitPrice = self::normalizeAmount($schoolTwoCoursesUnitPrice);

        return $this;
    }

    public function getSchoolThreeCoursesUnitPrice(): string
    {
        return self::normalizeAmount($this->schoolThreeCoursesUnitPrice);
    }

    public function setSchoolThreeCoursesUnitPrice(string $schoolThreeCoursesUnitPrice): static
    {
        $this->schoolThreeCoursesUnitPrice = self::normalizeAmount($schoolThreeCoursesUnitPrice);

        return $this;
    }

    public function getSchoolFourCoursesUnitPrice(): string
    {
        return self::normalizeAmount($this->schoolFourCoursesUnitPrice);
    }

    public function setSchoolFourCoursesUnitPrice(string $schoolFourCoursesUnitPrice): static
    {
        $this->schoolFourCoursesUnitPrice = self::normalizeAmount($schoolFourCoursesUnitPrice);

        return $this;
    }

    public function getSchoolAdditionalCoursesUnitPrice(): string
    {
        return self::normalizeAmount($this->schoolAdditionalCoursesUnitPrice);
    }

    public function setSchoolAdditionalCoursesUnitPrice(string $schoolAdditionalCoursesUnitPrice): static
    {
        $this->schoolAdditionalCoursesUnitPrice = self::normalizeAmount($schoolAdditionalCoursesUnitPrice);

        return $this;
    }

    public function getSchoolRegistrationFee(): string
    {
        return self::normalizeAmount($this->schoolRegistrationFee);
    }

    public function setSchoolRegistrationFee(string $schoolRegistrationFee): static
    {
        $this->schoolRegistrationFee = self::normalizeAmount($schoolRegistrationFee);

        return $this;
    }

    public function getDaycareOffSeasonDailyPrice(): string
    {
        return self::normalizeAmount($this->daycareOffSeasonDailyPrice);
    }

    public function setDaycareOffSeasonDailyPrice(string $daycareOffSeasonDailyPrice): static
    {
        $this->daycareOffSeasonDailyPrice = self::normalizeAmount($daycareOffSeasonDailyPrice);

        return $this;
    }

    public function getDaycarePeakSeasonDailyPrice(): string
    {
        return self::normalizeAmount($this->daycarePeakSeasonDailyPrice);
    }

    public function setDaycarePeakSeasonDailyPrice(string $daycarePeakSeasonDailyPrice): static
    {
        $this->daycarePeakSeasonDailyPrice = self::normalizeAmount($daycarePeakSeasonDailyPrice);

        return $this;
    }

    public function getHotelDailyPrice(): string
    {
        return self::normalizeAmount($this->hotelDailyPrice);
    }

    public function setHotelDailyPrice(string $hotelDailyPrice): static
    {
        $this->hotelDailyPrice = self::normalizeAmount($hotelDailyPrice);

        return $this;
    }

    public function getHotelServiceFee(): string
    {
        return self::normalizeAmount($this->hotelServiceFee);
    }

    public function setHotelServiceFee(string $hotelServiceFee): static
    {
        $this->hotelServiceFee = self::normalizeAmount($hotelServiceFee);

        return $this;
    }

    public function getHotelTravelProtectionBaseFee(): string
    {
        return self::normalizeAmount($this->hotelTravelProtectionBaseFee);
    }

    public function setHotelTravelProtectionBaseFee(string $hotelTravelProtectionBaseFee): static
    {
        $this->hotelTravelProtectionBaseFee = self::normalizeAmount($hotelTravelProtectionBaseFee);

        return $this;
    }

    public function getHotelTravelProtectionAdditionalDailyFee(): string
    {
        return self::normalizeAmount($this->hotelTravelProtectionAdditionalDailyFee);
    }

    public function setHotelTravelProtectionAdditionalDailyFee(string $hotelTravelProtectionAdditionalDailyFee): static
    {
        $this->hotelTravelProtectionAdditionalDailyFee = self::normalizeAmount($hotelTravelProtectionAdditionalDailyFee);

        return $this;
    }

    public function getHotelSingleRoomDaycareDailyPrice(): string
    {
        return self::normalizeAmount($this->hotelSingleRoomDaycareDailyPrice);
    }

    public function setHotelSingleRoomDaycareDailyPrice(string $hotelSingleRoomDaycareDailyPrice): static
    {
        $this->hotelSingleRoomDaycareDailyPrice = self::normalizeAmount($hotelSingleRoomDaycareDailyPrice);

        return $this;
    }

    public function getHotelSingleRoomHotelDailyPrice(): string
    {
        return self::normalizeAmount($this->hotelSingleRoomHotelDailyPrice);
    }

    public function setHotelSingleRoomHotelDailyPrice(string $hotelSingleRoomHotelDailyPrice): static
    {
        $this->hotelSingleRoomHotelDailyPrice = self::normalizeAmount($hotelSingleRoomHotelDailyPrice);

        return $this;
    }

    public function getHotelHeatCycleDailyPrice(): string
    {
        return self::normalizeAmount($this->hotelHeatCycleDailyPrice);
    }

    public function setHotelHeatCycleDailyPrice(string $hotelHeatCycleDailyPrice): static
    {
        $this->hotelHeatCycleDailyPrice = self::normalizeAmount($hotelHeatCycleDailyPrice);

        return $this;
    }

    public function getHotelMedicationPerAdministrationPrice(): string
    {
        return self::normalizeAmount($this->hotelMedicationPerAdministrationPrice);
    }

    public function setHotelMedicationPerAdministrationPrice(string $hotelMedicationPerAdministrationPrice): static
    {
        $this->hotelMedicationPerAdministrationPrice = self::normalizeAmount($hotelMedicationPerAdministrationPrice);

        return $this;
    }

    public function getHotelSupplementPerAdministrationPrice(): string
    {
        return self::normalizeAmount($this->hotelSupplementPerAdministrationPrice);
    }

    public function setHotelSupplementPerAdministrationPrice(string $hotelSupplementPerAdministrationPrice): static
    {
        $this->hotelSupplementPerAdministrationPrice = self::normalizeAmount($hotelSupplementPerAdministrationPrice);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = AppClock::now();

        return $this;
    }

    /** @return Collection<int, HotelPeakSeason> */
    public function getHotelPeakSeasons(): Collection
    {
        return $this->hotelPeakSeasons;
    }

    public function addHotelPeakSeason(HotelPeakSeason $hotelPeakSeason): static
    {
        if (!$this->hotelPeakSeasons->contains($hotelPeakSeason)) {
            $this->hotelPeakSeasons->add($hotelPeakSeason);
            $hotelPeakSeason->setPricingConfig($this);
        }

        return $this;
    }

    public function removeHotelPeakSeason(HotelPeakSeason $hotelPeakSeason): static
    {
        if ($this->hotelPeakSeasons->removeElement($hotelPeakSeason) && $hotelPeakSeason->getPricingConfig() === $this) {
            $hotelPeakSeason->setPricingConfig(null);
        }

        return $this;
    }

    public function clearHotelPeakSeasons(): static
    {
        foreach ($this->hotelPeakSeasons->toArray() as $hotelPeakSeason) {
            $this->removeHotelPeakSeason($hotelPeakSeason);
        }

        return $this;
    }

    private static function normalizeAmount(string $amount): string
    {
        return number_format((float) str_replace(',', '.', trim($amount)), 2, '.', '');
    }
}
