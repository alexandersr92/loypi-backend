<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GenericExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $data;
    protected array $headers;
    protected ?string $sheetName;

    public function __construct(array $data, array $headers, ?string $sheetName = null)
    {
        $this->data = $data;
        $this->headers = $headers;
        $this->sheetName = $sheetName;
    }

    public function array(): array
    {
        // Convertir objetos a arrays si es necesario
        $result = [];
        foreach ($this->data as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            
            // Si el array tiene claves asociativas, ordenarlas según los headers
            if (!empty($row) && !is_numeric(key($row))) {
                $orderedRow = [];
                foreach ($this->headers as $header) {
                    $value = $row[$header] ?? '';
                    
                    // Convertir valores complejos a string
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    } elseif ($value === null) {
                        $value = '';
                    } elseif (is_bool($value)) {
                        $value = $value ? 'Sí' : 'No';
                    }
                    
                    $orderedRow[] = $value;
                }
                $result[] = $orderedRow;
            } else {
                // Si ya es un array indexado, asegurar que tenga el mismo número de elementos que headers
                $rowValues = array_values($row);
                while (count($rowValues) < count($this->headers)) {
                    $rowValues[] = '';
                }
                $result[] = array_slice($rowValues, 0, count($this->headers));
            }
        }
        
        return $result;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function title(): string
    {
        return $this->sheetName ?? 'Sheet1';
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para la primera fila (headers)
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Ajustar altura de la primera fila
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Alineación para las celdas de datos
        if ($sheet->getHighestRow() > 1) {
            $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
    }

    public function columnWidths(): array
    {
        $widths = [];
        $column = 'A';
        
        foreach ($this->headers as $index => $header) {
            // Ancho automático basado en el contenido del header
            $width = max(strlen($header) + 5, 15);
            $widths[$column] = min($width, 50); // Máximo 50 caracteres
            $column++;
        }

        return $widths;
    }
}

