<?php

namespace Tests\Unit;

use App\Helpers\DateHelper;
use Carbon\Carbon;
use Tests\TestCase;

class DateHelperTest extends TestCase
{
    /** @test */
    public function it_formats_date_in_french()
    {
        $date = Carbon::create(2024, 1, 15, 12, 30, 0);

        $formatted = DateHelper::formatFrench($date);

        $this->assertStringContainsString('15', $formatted);
        $this->assertStringContainsString('01', $formatted);
        $this->assertStringContainsString('2024', $formatted);
    }

    /** @test */
    public function it_formats_datetime_in_french()
    {
        $date = Carbon::create(2024, 1, 15, 14, 30, 0);

        $formatted = DateHelper::formatDateTimeFrench($date);

        $this->assertStringContainsString('janvier', strtolower($formatted));
        $this->assertStringContainsString('14:30', $formatted);
    }

    /** @test */
    public function it_checks_if_date_is_within_range()
    {
        $date = Carbon::create(2024, 1, 15);
        $start = Carbon::create(2024, 1, 1);
        $end = Carbon::create(2024, 1, 31);

        $isWithin = DateHelper::isWithinRange($date, $start, $end);

        $this->assertTrue($isWithin);
    }

    /** @test */
    public function it_returns_false_when_date_outside_range()
    {
        $date = Carbon::create(2024, 2, 15);
        $start = Carbon::create(2024, 1, 1);
        $end = Carbon::create(2024, 1, 31);

        $isWithin = DateHelper::isWithinRange($date, $start, $end);

        $this->assertFalse($isWithin);
    }

    /** @test */
    public function it_gets_day_of_week()
    {
        // Monday
        $date = Carbon::create(2024, 1, 15); // This is a Monday

        $dayOfWeek = DateHelper::getDayOfWeek($date);

        $this->assertEquals(1, $dayOfWeek);
    }

    /** @test */
    public function it_checks_business_hours()
    {
        // 10:00 AM - within business hours (9-18)
        $time = Carbon::create(2024, 1, 15, 10, 0, 0);

        $isBusinessHours = DateHelper::isBusinessHours($time);

        $this->assertTrue($isBusinessHours);
    }

    /** @test */
    public function it_returns_false_outside_business_hours()
    {
        // 8:00 AM - before business hours (9-18)
        $time = Carbon::create(2024, 1, 15, 8, 0, 0);

        $isBusinessHours = DateHelper::isBusinessHours($time);

        $this->assertFalse($isBusinessHours);
    }

    /** @test */
    public function it_supports_custom_business_hours()
    {
        // 11:00 AM - within custom hours (10-17)
        $time = Carbon::create(2024, 1, 15, 11, 0, 0);

        $isBusinessHours = DateHelper::isBusinessHours($time, '10:00', '17:00');

        $this->assertTrue($isBusinessHours);
    }
}
