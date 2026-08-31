<?php

namespace Tests\Unit;

use App\Services\Trips\DistanceCalculator;
use Tests\TestCase;

class DistanceCalculatorTest extends TestCase
{
    public function test_it_computes_the_known_great_circle_distance_between_lhr_and_jfk(): void
    {
        $calculator = new DistanceCalculator;

        $miles = $calculator->milesBetween(51.4700, -0.4543, 40.6413, -73.7781);

        // Published great-circle distance LHR-JFK is ~3451 miles.
        $this->assertEqualsWithDelta(3451, $miles, 20);
    }

    public function test_the_distance_between_a_point_and_itself_is_zero(): void
    {
        $calculator = new DistanceCalculator;

        $this->assertEqualsWithDelta(0, $calculator->milesBetween(51.47, -0.4543, 51.47, -0.4543), 0.0001);
    }
}
