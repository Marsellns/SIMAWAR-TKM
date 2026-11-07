<?php

namespace Tests\Unit;

use App\Support\AddressPlusCode;
use App\Support\SiteCoordinates;
use PHPUnit\Framework\TestCase;

class AddressPlusCodeTest extends TestCase
{
    public function test_short_code_is_recovered_only_when_it_has_one_location_in_the_supplied_locality(): void
    {
        $geometry = ['type' => 'Polygon', 'coordinates' => [[[106.4,-6.8],[107.3,-6.8],[107.3,-6.3],[106.4,-6.3],[106.4,-6.8]]]];
        $point = AddressPlusCode::fromAddress('G222+22, Jl. Contoh, Jonggol', $geometry);
        $this->assertEqualsWithDelta(-6.4999375, $point['lat'], .0000001);
        $this->assertEqualsWithDelta(107.0000625, $point['lng'], .0000001);
        $this->assertNull(AddressPlusCode::fromAddress('G222+22, Jonggol', null));
        $this->assertNull(AddressPlusCode::fromAddress('Jl. Contoh, Bogor', $geometry));
        $ambiguous = ['type' => 'Polygon', 'coordinates' => [[[106,-8],[109,-8],[109,-5],[106,-5],[106,-8]]]];
        $this->assertNull(AddressPlusCode::fromAddress('G222+22', $ambiguous));
    }

    public function test_invalid_zero_swapped_and_out_of_country_coordinates_are_rejected(): void
    {
        $this->assertNull(SiteCoordinates::point('', '107.1'));
        $this->assertNull(SiteCoordinates::point(0, 0));
        $this->assertNull(SiteCoordinates::point(107.1, -6.2));
        $this->assertNull(SiteCoordinates::point('unknown', '107.1'));
        $this->assertSame(['lat' => -6.2, 'lng' => 107.1], SiteCoordinates::fromDetails(['latitude' => '-6,2', 'longtitude' => '107,1']));
    }
}
