<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, string>>  $sampleRows
     */
    public function __construct(
        protected array $headings,
        protected array $sampleRows = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return $this->sampleRows;
    }
}
