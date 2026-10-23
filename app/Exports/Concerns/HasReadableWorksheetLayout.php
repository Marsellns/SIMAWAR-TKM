<?php

namespace App\Exports\Concerns;

use App\Support\ExcelWorksheetLayout;
use Maatwebsite\Excel\Events\AfterSheet;

trait HasReadableWorksheetLayout
{
    public function registerEvents(): array
    {
        return [AfterSheet::class => [ExcelWorksheetLayout::class, 'apply']];
    }
}
