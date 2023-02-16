<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class DocumentPrevioObsImport implements ToArray
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
                'observaciones' => $row[42]
            );
        }
    }

    public function getArray(): array
    {
        return $this->data;
    }
}
