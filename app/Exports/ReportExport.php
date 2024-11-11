<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportExport implements FromArray, WithHeadings, WithEvents, WithTitle
{
    protected $generalData;
    protected $detailedData;

    public function __construct(protected $data)
    {
        $this->generalData = $data["general"];
        $this->detailedData = $data["detailed"];
    }

    public function array(): array
    {
        $sheetData = [];

        $sheetData[] = ['General Hospital Report'];
        $sheetData[] = ['', '', '', '', '', ''];
        $sheetData[] = ['Hospital ID', 'Hospital Title', 'Hospital Address', 'Total Service Quantity', 'Total Sum', 'Average services per day'];
        foreach ($this->generalData as $general) {
            $sheetData[] = [
                $general['hospitalId'],
                $general['hospitalTitle'],
                $general['hospitalAddress'],
                $general['totalServiceQuantity'],
                $general['totalSum'],
                number_format($general['averageServicesPerDay'], 2),
            ];
        }

        $sheetData[] = ['', '', '', '', '', ''];
        $sheetData[] = ['', '', '', '', '', ''];
        $sheetData[] = ['', '', '', '', '', ''];
        $sheetData[] = ['', '', '', '', '', ''];

        $sheetData[] = ['Detailed Hospital Report'];
        $sheetData[] = ['', '', '', '', '', ''];
        $sheetData[] = ['Service ID', 'Service name', 'Quantity', 'Service Total sum'];
        foreach ($this->detailedData as $detailed) {
            $sheetData[] = [
                $detailed['serviceId'],
                $detailed['serviceName'],
                $detailed['quantity'],
                $detailed['serviceTotalSum'],
            ];
        }

        return $sheetData;
    }

    public function headings(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1. A1:C1 - Merge cells and center text with font size 14
                $sheet->mergeCells('A1:C1');
                $sheet->getStyle('A1:C1')->applyFromArray([
                    'font' => [
                        'size' => 14,
                        'bold' => true
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->setCellValue('A1', 'General Hospital Report'); // Set the text for merged cells
    
                // 2. A3:F3 - General Data headings with borders and bold
                $sheet->getStyle('A3:F3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 3. A4:F4 - General Data, the content row (no borders, font not bold)
                $sheet->getStyle('A4:F4')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 4. A9:C9 - Merge cells and center text with font size 14
                $sheet->mergeCells('A9:C9');
                $sheet->getStyle('A9:C9')->applyFromArray([
                    'font' => [
                        'size' => 14,
                        'bold' => true
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->setCellValue('A9', 'Detailed Hospital Report'); // Set the text for merged cells
    
                // 5. A11:D11 - Detailed Data headings with borders and bold
                $sheet->getStyle('A11:D11')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 6. A12:D12 - Detailed Data, the content row (no borders, font not bold)
                $sheet->getStyle('A12:D12')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $columns = ['A', 'B', 'C', 'D', 'E', 'F'];

                foreach ($columns as $column) {
                    $sheet->getColumnDimension($column)->setWidth(30);
                }
            }
        ];
    }

    public function title(): string
    {
        return 'Hospital Report';
    }

}
