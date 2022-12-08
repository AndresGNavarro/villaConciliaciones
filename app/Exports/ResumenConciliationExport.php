<?php

namespace App\Exports;

use App\Models\Period;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ResumenConciliationExport implements WithEvents
{
    public $arrayAllTicketsBsp;
    public $grandTotalBsp;
    public $periodReference;
    public $iataReference;

    public function __construct(array $array)
    {
        $this->arrayAllTicketsBsp = $array[0];
        $this->grandTotalBsp = $array[1];
        $this->periodReference = $array[2];
        $this->iataReference = $array[3];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $tableHead = [
                    'font' => [
                        'color' => [
                            'rgb' => 'FFFFFF'
                        ],
                        'bold' => true,
                        'size' => 11
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '538ED5'
                        ]
                    ],
                ];

                $style2 = [
                    'font' => [
                        'rgb' => 'FFFFFF',
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
                $event->sheet->getDelegate()->setCellValue('A1', "RESUMEN BSP PERIODO: ".$iataPeriodDescription[0]['description']);
                $event->sheet->getDelegate()->setCellValue('A2', "VENTAS");
                $event->sheet->getDelegate()->setCellValue('B2', "TARIFA");
                $event->sheet->getDelegate()->setCellValue('C2', "IMPUESTOS");
                $event->sheet->getDelegate()->setCellValue('D2', "COMISIÓN");
                $event->sheet->getDelegate()->setCellValue('E2', "IVA COMISIÓN");
                $event->sheet->getDelegate()->setCellValue('F2', "PAGO NETO");

                /* Merge heading */
                $event->sheet->getDelegate()->mergeCells("A1:F1");
                $event->sheet->getDelegate()->mergeCells("A3:B3");
                /* Set font style */
                $event->sheet->getDelegate()->getStyle('A1')->getFont()->setSize(14)->setBold(true);
                $event->sheet->getDelegate()->getStyle('A4')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A7')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A9')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A12')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A14')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A19')->getFont()->setBold(true);
                // set cell alignment
                $event->sheet->getDelegate()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A1:F1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THICK);

                //setting column width
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(15);

                //set font style and background color E
                $event->sheet->getDelegate()->getStyle('A2:F2')->applyFromArray($tableHead);
                $event->sheet->getDelegate()->getStyle('B5')
                    ->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                $sheet = $event->sheet->getDelegate();
                $this->populateSheet($sheet,$style2);
            }
        ];
    }

    private function populateSheet($sheet, $style)
    {

        $sheet->setCellValue('A3', "IATA:" . $this->iataReference);
        $sheet->setCellValue('A4', "CONTADO");
        $sheet->setCellValue('A5', "TKTS NACIONAL");
        $sheet->setCellValue('A6', "TKTS INTERNACIONAL");
        $sheet->setCellValue('A7', "SUBTOTAL CONTADO");
        $sheet->setCellValue('A9', "CRÉDITO");
        $sheet->setCellValue('A10', "TKTS NACIONAL");
        $sheet->setCellValue('A11', "TKTS INTERNACIONAL");
        $sheet->setCellValue('A12', "SUBTOTAL CRÉDITO");
        $sheet->setCellValue('A14', "TOTAL VENTA");
        $sheet->setCellValue('A16', "TOTAL REEMBOLSOS");
        $sheet->setCellValue('A17', "TOTAL DÉBITOS ADM");
        $sheet->setCellValue('A18', "TOTAL CRÉDITOS ACM");
        $sheet->setCellValue('A19', "TOTAL GENERAL");

        //NACIONAL TKTS
        $valorContadoTktsNacional = $valorCreditoTktsNacional = $valorNetoContadoTktsNacional =
            $valorNetoCreditoTktsNacional = $valorContadoTktsNacionalImp = $valorCreditoTktsNacionalImp =
            $valorContadoTktsNacionalComision = $valorCreditoTktsNacionalComision =
            $valorContadoTktsNacionalIvaComision = $valorCreditoTktsNacionalIvaComision = 0;
        //INTERNACIONAL TKTS
        $valorContadoTktsInternacional = $valorCreditoTktsInternacional = $valorNetoContadoTktsInternacional =
            $valorNetoCreditoTktsInternacional = $valorContadoTktsInternacionalImp = $valorCreditoTktsInternacionalImp =
            $valorContadoTktsInternacionalComision = $valorCreditoTktsInternacionalComision =
            $valorContadoTktsInternacionalIvaComision = $valorCreditoTktsInternacionalIvaComision = 0;
        //REEMBOLSOS, DEBITO, CREDITO
        $valorReembolsos = $valorNetoReembolsos = $valorReembolsosImp = $valorReembolsosComision = $valorReembolsosIvaComision = 0;
        $valorDebitos = $valorNetoDebitos = $valorDebitosImp = $valorDebitosComision = $valorDebitosIvaComision = 0;
        $valorCreditos = $valorNetoCreditos = $valorCreditosImp = $valorCreditosComision =  $valorCreditosIvaComision = 0;

        $iteration = 5;

        foreach ($this->arrayAllTicketsBsp as $resultRow) {

            // Create cell definitions
            $A = "A" . ($iteration);
            $B = "B" . ($iteration);
            $C = "C" . ($iteration);
            $D = "D" . ($iteration);
            $E = "E" . ($iteration);
            $F = "F" . ($iteration);

            if ($resultRow['TipoBoleto'] == 'RFND') {

                $valorReembolsos += $resultRow['Tarifa'];
                $valorNetoReembolsos += $resultRow['Total'];
                $valorReembolsosImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorReembolsosComision += $resultRow['ValorComisionStd'] + $resultRow['ValorComisionSup'];
                $valorReembolsosIvaComision += $resultRow['ImpuestoComision'];
            } else if ($resultRow['TipoBoleto'] == 'ADMA') {

                $valorDebitos += $resultRow['Tarifa'];
                $valorNetoDebitos += $resultRow['Total'];
                $valorDebitosImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorDebitosComision += $resultRow['ValorComisionStd'] + $resultRow['ValorComisionSup'];
                $valorDebitosIvaComision += $resultRow['ImpuestoComision'];
            } else if ($resultRow['TipoBoleto'] == 'ACMA') {

                $valorCreditos += $resultRow['Tarifa'];
                $valorNetoCreditos += $resultRow['Total'];
                $valorCreditosImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorCreditosComision += $resultRow['ValorComisionStd'] + $resultRow['ValorComisionSup'];
                $valorCreditosIvaComision += $resultRow['ImpuestoComision'];
            } else if ($resultRow['TipoAlcance'] == 'D' && $resultRow['CACC'] == 'CA') {

                $valorContadoTktsNacional += $resultRow['Tarifa'];
                $valorNetoContadoTktsNacional += $resultRow['Total'];
                $valorContadoTktsNacionalImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorContadoTktsNacionalComision += $resultRow['ValorComisionStd'] + $resultRow['ValorComisionSup'];
                $valorContadoTktsNacionalIvaComision += $resultRow['ImpuestoComision'];
            } else if ($resultRow['TipoAlcance'] == 'I' && $resultRow['CACC'] == 'CA') {

                $valorContadoTktsInternacional += $resultRow['Tarifa'];
                $valorNetoContadoTktsInternacional += $resultRow['Total'];
                $valorContadoTktsInternacionalImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorContadoTktsInternacionalComision += $resultRow['ValorComisionStd'] + $resultRow['ValorComisionSup'];
                $valorContadoTktsInternacionalIvaComision += $resultRow['ImpuestoComision'];
            } else if ($resultRow['TipoAlcance'] == 'D' && $resultRow['CACC'] == 'CC') {

                $valorCreditoTktsNacional += $resultRow['Tarifa'];
                $valorNetoCreditoTktsNacional += $resultRow['Total'];
                $valorCreditoTktsNacionalImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorCreditoTktsNacionalComision += $resultRow['ValorComisionStd'] + $resultRow['ValorComisionSup'];
                $valorCreditoTktsNacionalIvaComision += $resultRow['ImpuestoComision'];
            } else if ($resultRow['TipoAlcance'] == 'I' && $resultRow['CACC'] == 'CC') {

                $valorCreditoTktsInternacional += $resultRow['Tarifa'];
                $valorNetoCreditoTktsInternacional += $resultRow['Total'];
                $valorCreditoTktsInternacionalImp += $resultRow['Impuesto'] + $resultRow['TasasCargos'];
                $valorCreditoTktsInternacionalComision += (int)$resultRow['ValorComisionStd'] + (int)$resultRow['ValorComisionSup'];
                $valorCreditoTktsInternacionalIvaComision += $resultRow['ImpuestoComision'];
            }

            $cellRangeTarget = $B . ':' . $F;
            if ($iteration > 4) {
                $sheet->duplicateStyle($sheet->getStyle('B5'), $cellRangeTarget);
            }
            $iteration++;
        }

        $sheet->getStyle('B19:F19')->applyFromArray($style);

        $subTotalContadoTarifa = $valorContadoTktsNacional + $valorContadoTktsInternacional;
        $subTotalCreditoTarifa = $valorCreditoTktsNacional + $valorCreditoTktsInternacional;
        $subTotalContadoTarifaImp = $valorContadoTktsNacionalImp + $valorContadoTktsInternacionalImp;
        $subTotalCreditoTarifaImp = $valorCreditoTktsNacionalImp + $valorCreditoTktsInternacionalImp;
        $subTotalContadoNeto = $valorNetoContadoTktsNacional + $valorNetoContadoTktsInternacional;
        $subTotalCreditoNeto = $valorNetoCreditoTktsNacional + $valorNetoCreditoTktsInternacional;
        $subTotalContadoComision = $valorContadoTktsNacionalComision + $valorContadoTktsInternacionalComision;
        $subTotalContadoComisionIva = $valorContadoTktsNacionalIvaComision + $valorContadoTktsInternacionalIvaComision;
        $subTotalCreditoComision = $valorCreditoTktsNacionalComision + $valorCreditoTktsInternacionalComision;
        $subTotalCreditoComisionIva = $valorCreditoTktsNacionalIvaComision + $valorCreditoTktsInternacionalIvaComision;

        $totalVentaTarifa = $subTotalContadoTarifa + $subTotalCreditoTarifa;
        $totalVentaImpuestos = $subTotalContadoTarifaImp + $subTotalCreditoTarifaImp;
        $totalVentaComision = $subTotalContadoComision + $subTotalCreditoComision;
        $totalVentaIvaComision = $subTotalContadoComisionIva + $subTotalCreditoComisionIva;
        $totalVentaPagoNeto = $subTotalContadoNeto + $subTotalCreditoNeto;

        $sheet->setCellValue('B5', $valorContadoTktsNacional);
        $sheet->setCellValue('C5', $valorContadoTktsNacionalImp);
        $sheet->setCellValue('D5', $valorContadoTktsNacionalComision);
        $sheet->setCellValue('E5', $valorContadoTktsNacionalIvaComision);
        $sheet->setCellValue('F5', $valorNetoContadoTktsNacional);

        $sheet->setCellValue('B6', $valorContadoTktsInternacional);
        $sheet->setCellValue('C6', $valorContadoTktsInternacionalImp);
        $sheet->setCellValue('D6', $valorContadoTktsInternacionalComision);
        $sheet->setCellValue('E6', $valorContadoTktsInternacionalIvaComision);
        $sheet->setCellValue('F6', $valorNetoContadoTktsInternacional);

        $sheet->setCellValue('B7', $subTotalContadoTarifa);
        $sheet->setCellValue('C7', $subTotalContadoTarifaImp);
        $sheet->setCellValue('D7', $subTotalContadoComision);
        $sheet->setCellValue('E7', $subTotalContadoComisionIva);
        $sheet->setCellValue('F7', $subTotalContadoNeto);
        

        $sheet->setCellValue('B10', $valorCreditoTktsNacional);
        $sheet->setCellValue('C10', $valorCreditoTktsNacionalImp);
        $sheet->setCellValue('D10', $valorCreditoTktsNacionalComision);
        $sheet->setCellValue('E10', $valorCreditoTktsNacionalIvaComision);
        $sheet->setCellValue('F10', $valorNetoCreditoTktsNacional);

        $sheet->setCellValue('B11', $valorCreditoTktsInternacional);
        $sheet->setCellValue('C11', $valorCreditoTktsInternacionalImp);
        $sheet->setCellValue('D11', $valorCreditoTktsInternacionalComision);
        $sheet->setCellValue('E11', $valorCreditoTktsInternacionalIvaComision);
        $sheet->setCellValue('F11', $valorNetoCreditoTktsInternacional);

        $sheet->setCellValue('B12', $subTotalCreditoTarifa);
        $sheet->setCellValue('C12', $subTotalCreditoTarifaImp);
        $sheet->setCellValue('D12', $subTotalCreditoComision);
        $sheet->setCellValue('E12', $subTotalCreditoComisionIva);
        $sheet->setCellValue('F12', $subTotalCreditoNeto);

        $sheet->setCellValue('B14', $totalVentaTarifa);
        $sheet->setCellValue('C14', $totalVentaImpuestos);
        $sheet->setCellValue('D14', $totalVentaComision);
        $sheet->setCellValue('E14', $totalVentaIvaComision );
        $sheet->setCellValue('F14', $totalVentaPagoNeto);

        $sheet->setCellValue('B16', $valorReembolsos);
        $sheet->setCellValue('C16', $valorReembolsosImp);
        $sheet->setCellValue('D16', $valorReembolsosComision);
        $sheet->setCellValue('E16', $valorReembolsosIvaComision);
        $sheet->setCellValue('F16', $valorNetoReembolsos);

        $sheet->setCellValue('B17', $valorDebitos);
        $sheet->setCellValue('C17', $valorDebitosImp);
        $sheet->setCellValue('D17', $valorDebitosComision);
        $sheet->setCellValue('E17', $valorDebitosIvaComision);
        $sheet->setCellValue('F17', $valorNetoDebitos);
        

        $sheet->setCellValue('B18', $valorCreditos);
        $sheet->setCellValue('C18', $valorCreditosImp);
        $sheet->setCellValue('D18', $valorCreditosComision);
        $sheet->setCellValue('E18', $valorCreditosIvaComision);
        $sheet->setCellValue('F18', $valorNetoCreditos);

        $sheet->setCellValue('B19', $totalVentaTarifa+$valorReembolsos+$valorDebitos+$valorCreditos);
        $sheet->setCellValue('C19', $totalVentaImpuestos+$valorReembolsosImp+$valorDebitosImp+$valorCreditosImp);
        $sheet->setCellValue('D19', $totalVentaComision+$valorReembolsosComision+$valorDebitosComision+$valorCreditosComision);
        $sheet->setCellValue('E19', $totalVentaIvaComision+$valorReembolsosIvaComision+$valorDebitosIvaComision+$valorCreditosIvaComision);
        $sheet->setCellValue('F19', $this->grandTotalBsp);
    }
}
