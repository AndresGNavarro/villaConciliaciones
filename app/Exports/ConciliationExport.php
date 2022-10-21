<?php

namespace App\Exports;

use App\Models\Period;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class ConciliationExport implements WithEvents
{
    public $newArrayResultTickets;
    public $valorDiferencias;
    public $valorReportePrevio;
    public $grandTotal;
    public $periodReference;
    public $iata;
    
    
    public function __construct(array $array)
    {
        $this->newArrayResultTickets = $array[0];
        $this->valorDiferencias = $array[1];
        $this->valorReportePrevio = $array[2];
        $this->grandTotal = $array[3];
        $this->periodReference = $array[4];
        $this->iata = $array[5];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
            // Todos los encabezados: establece la fuente en 14
            //$cellRange = 'A1:W1';
            //$event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            // Establece la altura de la primera fila en 20
            //$event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(20);
            // Establecer el texto en el rango de A1: D4 para ajustar automáticamente
            //$event->sheet->getDelegate()->getStyle('A1:D4')->getAlignment()->setWrapText(true);
            $tableHead = [
                'font'=>[
                    'color'=>[
                        'rgb'=>'FFFFFF'
                    ],
                    'bold'=>true,
                    'size'=>11
                ],
                'fill'=>[
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '538ED5'
                    ]
                ],
            ];
            
            $style1 = [
                'font' => [
                    'rgb'=>'FFFFFF',
                    'bold' => true
                ],
                'borders' => [
                    'diagonalDirection' => Borders::DIAGONAL_BOTH,
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'startColor' => [
                        'argb' => 'FFA0A0A0',
                    ],
                    'endColor' => [
                        'argb' => 'FFFFFFFF',
                    ],
                ],
            ];
            
            $style2= [
                'font' => [
                    'rgb'=>'FFFFFF',
                    'bold' => true
                ],
                'borders' => [
                    'diagonalDirection' => Borders::DIAGONAL_BOTH,
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'startColor' => [
                        'argb' => 'FFA0A0A0',
                    ],
                    'endColor' => [
                        'argb' => 'FFFFFFFF',
                    ],
                ],
            ];
            /* Headings */
            $iataPeriodDescription = Period::where('pkPeriod', $this->periodReference)->get()->toArray();
            $event->sheet->getDelegate()->setCellValue('A1',"DIFERENCIAS ".$iataPeriodDescription[0]['description']);
            $event->sheet->getDelegate()->setCellValue('A2',"IATA: ".$this->iata);
            $event->sheet->getDelegate()->setCellValue('G1',"RESUMEN");
            /* Merge heading */
            $event->sheet->getDelegate()->mergeCells("A1:E1");
            $event->sheet->getDelegate()->mergeCells("A2:B2");
            $event->sheet->getDelegate()->mergeCells("G1:M1");
            $event->sheet->getDelegate()->mergeCells("G4:J4");
            $event->sheet->getDelegate()->mergeCells("K4:M4");
            $event->sheet->getDelegate()->mergeCells("G6:J6");
            $event->sheet->getDelegate()->mergeCells("K6:M6");
            $event->sheet->getDelegate()->mergeCells("G8:J8");
            $event->sheet->getDelegate()->mergeCells("K8:M8");
            /* Set font style */
            $event->sheet->getDelegate()->getStyle('A1')->getFont()->setSize(20);
            $event->sheet->getDelegate()->getStyle('G1')->getFont()->setSize(20);

            // set cell alignment
            $event->sheet->getDelegate()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $event->sheet->getDelegate()->getStyle('G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $event->sheet->getDelegate()->getStyle('A1:E1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THICK);
            $event->sheet->getDelegate()->getStyle('G1:M1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THICK);

            //setting column width
            $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(5);
            $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(20);
            $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(40);
            $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(20);
            $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(12);
           
            //set font style and background color E
            $event->sheet->getDelegate()->getStyle('A3:E3')->applyFromArray($tableHead);
            $event->sheet->getDelegate()->getStyle('G4:J4')->applyFromArray($tableHead);
            $event->sheet->getDelegate()->getStyle('G6:J6')->applyFromArray($tableHead);
            $event->sheet->getDelegate()->getStyle('G8:J8')->applyFromArray($tableHead);
            $event->sheet->getDelegate()->getStyle('K4:M4')->applyFromArray($style2);
            $event->sheet->getDelegate()->getStyle('K6:M6')->applyFromArray($style2);
            $event->sheet->getDelegate()->getStyle('K8:M8')->applyFromArray($style2);

            $sheet = $event->sheet->getDelegate();
            $this->populateSheet($sheet);

            }
        ];
    
    }

    private function populateSheet($sheet){

        // Populate the static cells
        $sheet->setCellValue('A3',"L.A.");
        $sheet->setCellValue('B3',"FECHA");
        $sheet->setCellValue('C3',"CONCEPTO");
        $sheet->setCellValue('D3',"BOLETO");
        $sheet->setCellValue('E3',"TOTAL");
        $sheet->setCellValue('G4',"VALOR DEL REPORTE PREVIO:");
        $sheet->setCellValue('G6',"DIFERENCIAS CONCILIACIÓN:"); 
        $sheet->setCellValue('G8',"VALOR DE LA FACTURA BSP:"); 


        // Create the collection based on received ids
        //$orders = Order::whereIn('id', $this->orderIds)->get();

        // Party starts at row 4
        $iteration = 4;

        foreach ($this->newArrayResultTickets as $resultRow) {

            // Create cell definitions
            $A = "A".($iteration);
            $B = "B".($iteration);
            $C = "C".($iteration);
            $D = "D".($iteration);
            $E = "E".($iteration);

            // Populate dynamic content
            $sheet->setCellValue($A, $resultRow['L.A.']);
            $sheet->setCellValue($B, $resultRow['Fecha']);
            $sheet->setCellValue($C, $resultRow['Concepto']);
            $sheet->setCellValue($D, $resultRow['Boleto']);
            $sheet->setCellValue($E, $resultRow['Total']);

            $sheet->getStyle($B)
            ->getNumberFormat()
            ->setFormatCode(
                NumberFormat::FORMAT_DATE_YYYYMMDDSLASH
            );

            //$cellRangeTarget = $A.':'.$E;

            // Copy style of Row 3 onto new rows - RowHeight is not being copied, need to adjust manually...
            /* if($iteration > 3)
            {
                $sheet->duplicateStyle($sheet->getStyle('A3'), $cellRangeTarget);
                $sheet->getRowDimension($iteration)->setRowHeight(43);
            } */
            
            $iteration++;
        }

        $lastRow=$iteration-1;
        
        /* $sheet->getStyle('D'.$iteration)->applyFromArray($this->style1);   */
        $sheet->setCellValue('D'.$iteration , 'TOTAL:');
        $sheet->setCellValue('E'.$iteration , '=SUM(E3:E'.$lastRow.')');
        $sheet->setCellValue('K6' , $this->valorDiferencias);
        $sheet->setCellValue('K4' , $this->valorReportePrevio);
        $sheet->setCellValue('K8' , $this->grandTotal);

    }

}
