<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, WithEvents, ShouldAutoSize, WithTitle
{
    // Couleur de marque SMH, utilisée sur les reçus/factures/achats de l'app.
    private const BRAND = '3E4C21';

    protected $payments;

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    public function title(): string
    {
        return 'Paiements';
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            'Facture', 'Versement (GNF)', 'Total Payé (GNF)', 'Reste à Payer (GNF)', 'Payé par', 'Note', 'Date Paiement',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->numeroFacture,
            (float) $payment->versement,
            (float) $payment->total_paye,
            (float) $payment->reste,
            ucfirst($payment->paid_by),
            $payment->note,
            \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BRAND]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $sheet->getHighestRow();
                $lastCol = 'G';

                $sheet->getRowDimension(1)->setRowHeight(22);

                // Bordures + centrage vertical sur toutes les lignes de données
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E6E9D5']],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Bandes zébrées pour la lisibilité
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9F9F3']],
                        ]);
                    }
                }

                // Colonnes montants alignées à droite
                $sheet->getStyle("B2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Ligne de totaux
                if ($lastRow >= 2) {
                    $totalRow = $lastRow + 1;
                    $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                    $sheet->setCellValue("B{$totalRow}", "=SUM(B2:B{$lastRow})");
                    $sheet->setCellValue("C{$totalRow}", "=SUM(C2:C{$lastRow})");
                    $sheet->setCellValue("D{$totalRow}", "=SUM(D2:D{$lastRow})");

                    $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => self::BRAND]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F5EC']],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::BRAND]],
                        ],
                    ]);
                    $sheet->getStyle("B{$totalRow}:D{$totalRow}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("B{$totalRow}:D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->freezePane('A2');
            },
        ];
    }
}
