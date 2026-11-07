<?php

namespace Tests\Unit;

use App\Support\TracyCoordinateReader;
use PHPUnit\Framework\TestCase;

class TracyCoordinateReaderTest extends TestCase
{
    public function test_latest_valid_period_wins_and_conflicting_coordinates_are_rejected(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'tracy-coordinates-');
        file_put_contents($file, "\xEF\xBB\xBFyearmonth,siteid,latitude,longitude,kabupaten\n202606,BGR001,-6.5,106.8,Bogor\n202607,bgr001,-6.6,106.9,Bogor\n202605,BGR001,-6.4,106.7,Bogor\n202607,CONFLICT,-6.5,106.8,Bogor\n202607,CONFLICT,-6.6,106.9,Bogor\n202607,INVALID,106.8,-6.5,Bogor\n202699,BADPERIOD,-6.5,106.8,Bogor\n");
        try {
            $data = (new TracyCoordinateReader)->read($file);
            $this->assertSame(7, $data['rows']);
            $this->assertSame(2, $data['invalid_rows']);
            $this->assertSame(1, $data['ambiguous_sites']);
            $this->assertSame(['BGR001'], array_keys($data['sites']));
            $this->assertSame('202607', $data['sites']['BGR001']['period']);
            $this->assertSame(['lat' => -6.6, 'lng' => 106.9], $data['sites']['BGR001']['point']);
        } finally { unlink($file); }
    }
}
