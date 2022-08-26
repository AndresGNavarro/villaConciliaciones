<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class DocumentPrevioImport implements ToArray
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }
    public function array(array $rows)
    {
        foreach ($rows as $row) {
            $this->data[] = array(
                'lineaAerea' => $row[0],
                'numeroBoleto' => $row[2],
                'totalVentaTitle' => $row[4],
                'claveBoleto' => $row[6],
                'numeroFactura' => $row[7],
                'fechaEmision' => $row[13],
                'contado' => $row[18],
                'credito' => $row[22],
                'iva' => $row[24],
                'tua' => $row[26],
                'porcentajeComision' => $row[31],
                'comision' => $row[35],
                'ivaComision' => $row[38],
                'netoPagar' => $row[40]
            );
        }
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
