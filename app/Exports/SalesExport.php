<?php
// app/Exports/SalesExport.php

namespace App\Exports;

use App\Models\Sale;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalesExport implements FromView, WithTitle, WithEvents, ShouldAutoSize
{
    protected $request;
    protected $data;

    public function __construct($request = null, $data = null)
    {
        $this->request = $request;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Sales Export';
    }

    /**
     * @return View
     */
    public function view(): View
    {
        if ($this->data) {
            $sales = $this->data;
        } else {
            $query = Sale::with('product');

            // Apply filters if request exists
            if ($this->request) {
                if ($this->request->filled('numeroFacture')) {
                    $query->where('numeroFacture', 'like', '%' . $this->request->numeroFacture . '%');
                }
                if ($this->request->filled('product_id')) {
                    $query->where('product_id', $this->request->product_id);
                }
                if ($this->request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $this->request->date_from);
                }
                if ($this->request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $this->request->date_to);
                }
            }

            // Role-based filtering
            if (auth()->user()->isStoreScoped()) {
                $storeId = auth()->user()->assignedStoreId();
                $query->where('store_id', $storeId);
            }

            $sales = $query->orderBy('created_at', 'desc')->get();
        }

        return view('exports.sales', [
            'sales' => $sales,
            'totalAmount' => $sales->sum('prixTotal'),
            'totalInterest' => $sales->sum('interet'),
            'totalQuantity' => $sales->sum('quantity')
        ]);
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Style the header row
                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '3E4C21']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Get the last row
                $lastRow = $sheet->getHighestRow();

                // Style data rows
                $sheet->getStyle('A2:H' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E6E9D5']
                        ]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Style the total row if exists
                if ($lastRow > 2) {
                    $sheet->getStyle('A' . ($lastRow + 1) . ':H' . ($lastRow + 1))->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 11
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F4F5EC']
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_DOUBLE,
                                'color' => ['rgb' => '000000']
                            ]
                        ]
                    ]);
                }

                // Auto-size columns
                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
        ];
    }
}