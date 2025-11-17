<?php

namespace Tests\Unit;

use App\Helpers\DistanceHelper;
use Tests\TestCase;

class DistanceHelperTest extends TestCase
{
    /** @test */
    public function it_calculates_distance_correctly()
    {
        // Tunis to Carthage (approximately 15km)
        $lat1 = 36.8065;
        $lon1 = 10.1815;
        $lat2 = 36.8530;
        $lon2 = 10.3233;

        $distance = DistanceHelper::calculate($lat1, $lon1, $lat2, $lon2);

        // Should be around 15km (15000 meters)
        $this->assertGreaterThan(14000, $distance);
        $this->assertLessThan(16000, $distance);
    }

    /** @test */
    public function it_calculates_zero_distance_for_same_location()
    {
        $lat = 36.8065;
        $lon = 10.1815;

        $distance = DistanceHelper::calculate($lat, $lon, $lat, $lon);

        $this->assertEquals(0, $distance);
    }

    /** @test */
    public function it_formats_distance_in_meters()
    {
        $distance = 500;
        $formatted = DistanceHelper::format($distance);

        $this->assertEquals('500 m', $formatted);
    }

    /** @test */
    public function it_formats_distance_in_kilometers()
    {
        $distance = 1500;
        $formatted = DistanceHelper::format($distance);

        $this->assertEquals('1.5 km', $formatted);
    }

    /** @test */
    public function it_checks_if_within_radius()
    {
        $lat1 = 36.8065;
        $lon1 = 10.1815;
        $lat2 = 36.8070;
        $lon2 = 10.1820;

        // These coordinates are very close (< 100m)
        $isWithin = DistanceHelper::isWithinRadius($lat1, $lon1, $lat2, $lon2, 1000);

        $this->assertTrue($isWithin);
    }

    /** @test */
    public function it_returns_false_when_outside_radius()
    {
        $lat1 = 36.8065;
        $lon1 = 10.1815;
        $lat2 = 36.8530;
        $lon2 = 10.3233;

        // These coordinates are far apart (> 15km)
        $isWithin = DistanceHelper::isWithinRadius($lat1, $lon1, $lat2, $lon2, 1000);

        $this->assertFalse($isWithin);
    }

    /** @test */
    public function it_calculates_bounding_box()
    {
        $lat = 36.8065;
        $lon = 10.1815;
        $radius = 1000; // 1km

        $bbox = DistanceHelper::getBoundingBox($lat, $lon, $radius);

        $this->assertArrayHasKey('min_lat', $bbox);
        $this->assertArrayHasKey('max_lat', $bbox);
        $this->assertArrayHasKey('min_lon', $bbox);
        $this->assertArrayHasKey('max_lon', $bbox);

        $this->assertLessThan($lat, $bbox['min_lat']);
        $this->assertGreaterThan($lat, $bbox['max_lat']);
        $this->assertLessThan($lon, $bbox['min_lon']);
        $this->assertGreaterThan($lon, $bbox['max_lon']);
    }
}
