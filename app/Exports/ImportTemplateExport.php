<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportTemplateExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<string, array<int, array<int, mixed>>>  $dataSheets
     * @param  array<int, array<int, mixed>>  $guideRows
     */
    public function __construct(
        private readonly array $dataSheets,
        private readonly array $guideRows
    ) {}

    /**
     * @return array<int, ImportTemplateSheetExport>
     */
    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataSheets as $title => $rows) {
            $sheets[] = new ImportTemplateSheetExport($title, $rows);
        }

        $sheets[] = new ImportTemplateSheetExport('Petunjuk', $this->guideRows);

        return $sheets;
    }
}
