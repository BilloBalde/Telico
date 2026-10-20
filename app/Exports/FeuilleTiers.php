<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Une feuille : titre de période, en-têtes en gras, lignes. */
class FeuilleTiers implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $titre,
        private string $periode,
        private array $entetes,
        private array $lignes,
    ) {
    }

    public function title(): string
    {
        return $this->titre;
    }

    public function headings(): array
    {
        return [[$this->titre . ' — ventes tierces — ' . $this->periode], $this->entetes];
    }

    public function array(): array
    {
        return $this->lignes ?: [['Aucune donnée sur la période.']];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A3');

        return [
            1 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '3E4C21']]],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3E4C21']],
            ],
        ];
    }
}
