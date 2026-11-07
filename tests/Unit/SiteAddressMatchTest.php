<?php

namespace Tests\Unit;

use App\Support\SiteAddressMatch;
use PHPUnit\Framework\TestCase;

class SiteAddressMatchTest extends TestCase
{
    private function boundary(): array
    {
        return ['type'=>'Polygon','coordinates'=>[[[106.4,-6.9],[107.3,-6.9],[107.3,-6.3],[106.4,-6.3],[106.4,-6.9]]]];
    }

    private function feature(array $properties = [], float $lat = -6.6, float $lng = 106.8): array
    {
        return ['properties'=>array_merge(['countrycode'=>'ID','osm_key'=>'highway','name'=>'Jalan Contoh Cendana','district'=>'Tamansari','extent'=>[$lng-.001,$lat+.001,$lng+.001,$lat-.001]], $properties), 'geometry'=>['type'=>'Point','coordinates'=>[$lng,$lat]]];
    }

    public function test_shorter_road_name_and_wrong_district_are_rejected(): void
    {
        $address = 'Jl. Contoh Cendana, Desa Contoh, Kec. Tamansari, Kab. Bogor';
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['name'=>'Jalan Contoh'])], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['district'=>'Bojong Gede'])], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['district'=>null])], $this->boundary()));
        $match = SiteAddressMatch::select($address, [$this->feature()], $this->boundary());
        $this->assertSame('street', $match['precision']);
        $this->assertSame('Tamansari', $match['district']);
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['osm_key'=>'amenity','osm_type'=>'W','osm_value'=>'police','type'=>'house','street'=>'Jalan Contoh Cendana'])], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['osm_type'=>'N','osm_value'=>'bus_stop'])], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['extent'=>[106.7,-6.5,106.9,-6.7]])], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['extent'=>null])], $this->boundary()));
    }

    public function test_house_number_is_verified_and_city_centers_never_match(): void
    {
        $address = 'Jl. Contoh Cendana No. 12, Kec. Tamansari, Bogor';
        $this->assertSame('Jalan Contoh Cendana No. 12, Bogor, Indonesia', SiteAddressMatch::streetQuery($address, 'Bogor'));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['osm_key'=>'place','osm_value'=>'city'])], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['housenumber'=>'13'])], $this->boundary()));
        $match = SiteAddressMatch::select($address, [$this->feature(['housenumber'=>'12'])], $this->boundary());
        $this->assertSame('building', $match['precision']);
        $match = SiteAddressMatch::select(str_replace('12', '12A', $address), [$this->feature(['housenumber'=>'12 A'])], $this->boundary());
        $this->assertSame('building', $match['precision']);
    }

    public function test_multiple_distinct_matches_and_out_of_region_results_are_rejected(): void
    {
        $address = 'Jl. Contoh Cendana, Kec. Tamansari, Bogor';
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(), $this->feature([], -6.7, 106.9)], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature([], -7.2, 106.9)], $this->boundary()));
        $this->assertNull(SiteAddressMatch::select($address, [$this->feature(['countrycode'=>'MY'])], $this->boundary()));
    }
}
