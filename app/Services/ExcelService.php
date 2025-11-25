<?php

namespace App\Services;

use App\Exports\GenericExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExcelService
{
    protected string $disk;
    protected string $pathPrefix;

    public function __construct()
    {
        // Usar 'public' para que los archivos sean accesibles públicamente
        $this->disk = 'public';
        $this->pathPrefix = 'exports';
    }

    /**
     * Genera un archivo Excel y retorna la URL de descarga
     *
     * @param array|Collection $data Array o Collection de datos (cada elemento es una fila)
     * @param array $headers Headers de las columnas (opcional, se infieren del primer elemento si no se proporciona)
     * @param string $filename Nombre del archivo sin extensión
     * @param string|null $sheetName Nombre de la hoja (opcional)
     * @return array{url: string, path: string, filename: string}
     * @throws \Exception
     */
    public function generate(
        array|Collection $data,
        ?array $headers = null,
        string $filename = 'export',
        ?string $sheetName = null
    ): array {
        // Convertir Collection a array si es necesario
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        // Si no hay datos, retornar error
        if (empty($data)) {
            throw new \Exception('No data provided to generate Excel file.');
        }

        // Si no se proporcionan headers, inferirlos del primer elemento
        if ($headers === null) {
            $firstRow = reset($data);
            if (is_array($firstRow)) {
                $headers = array_keys($firstRow);
            } else {
                throw new \Exception('Cannot infer headers from data. Please provide headers explicitly.');
            }
        }

        // Generar nombre único del archivo
        $uniqueFilename = $this->generateUniqueFilename($filename);
        $filePath = "{$this->pathPrefix}/{$uniqueFilename}";

        // Crear la clase export
        $export = new GenericExport($data, $headers, $sheetName);

        try {
            // Generar el archivo Excel usando store() que es más confiable
            // El método store() retorna true si tiene éxito
            $result = Excel::store($export, $filePath, $this->disk, \Maatwebsite\Excel\Excel::XLSX);
            
            if (!$result) {
                throw new \Exception('Excel::store() returned false.');
            }
            
            // Verificar que el archivo se haya creado correctamente
            if (!Storage::disk($this->disk)->exists($filePath)) {
                throw new \Exception('Failed to generate Excel file - file does not exist.');
            }

            // Verificar que el archivo tenga contenido
            $fileSize = Storage::disk($this->disk)->size($filePath);
            if ($fileSize === 0) {
                Storage::disk($this->disk)->delete($filePath);
                throw new \Exception('Generated Excel file is empty.');
            }

            // Verificar que el archivo sea un Excel válido (debe empezar con PK para ZIP/XLSX)
            $fileContent = Storage::disk($this->disk)->get($filePath);
            if (empty($fileContent) || substr($fileContent, 0, 2) !== 'PK') {
                Storage::disk($this->disk)->delete($filePath);
                throw new \Exception('Generated file is not a valid Excel file (missing ZIP signature).');
            }

            // Obtener la URL pública
            $url = Storage::disk($this->disk)->url($filePath);

            return [
                'url' => $url,
                'path' => $filePath,
                'filename' => $uniqueFilename,
            ];
        } catch (\Exception $e) {
            // Limpiar archivo si existe pero está corrupto
            if (Storage::disk($this->disk)->exists($filePath)) {
                Storage::disk($this->disk)->delete($filePath);
            }
            throw new \Exception('Error generating Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Genera un nombre único para el archivo
     *
     * @param string $filename
     * @return string
     */
    protected function generateUniqueFilename(string $filename): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        return "{$sanitized}_{$timestamp}.xlsx";
    }

    /**
     * Elimina un archivo Excel del storage
     *
     * @param string $path Path del archivo
     * @return bool
     */
    public function delete(string $path): bool
    {
        // Si es una URL, extraer el path
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = $this->extractPathFromUrl($path);
        }

        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }

        return false;
    }

    /**
     * Extrae el path del storage desde una URL
     *
     * @param string $url
     * @return string
     */
    protected function extractPathFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Remover /storage si existe
        $path = str_replace('/storage/', '', $path);
        $path = ltrim($path, '/');

        return $path;
    }
}

