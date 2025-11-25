<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    protected string $disk;
    protected string $pathPrefix;

    public function __construct()
    {
        $this->disk = config('uploads.disk', 'public');
        $this->pathPrefix = config('uploads.path_prefix', 'uploads');
    }

    /**
     * Sube un archivo y retorna la URL pública
     *
     * @param UploadedFile $file
     * @param string $model Nombre del modelo (campaign, business, user, etc.)
     * @param string $field Nombre del campo (banner, logo, avatar, etc.)
     * @return array{url: string, path: string, filename: string, size: int, mime_type: string, dimensions?: array}
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $model, string $field): array
    {
        // Validar que el modelo y campo existan en la configuración
        $this->validateModelAndField($model, $field);

        // Generar el path del archivo
        $path = $this->generatePath($file, $model, $field);

        // Guardar el archivo
        $storedPath = Storage::disk($this->disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        // Obtener información del archivo
        $size = Storage::disk($this->disk)->size($storedPath);
        $mimeType = Storage::disk($this->disk)->mimeType($storedPath);

        // Si es una imagen, obtener dimensiones
        $dimensions = null;
        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = $this->getImageDimensions($storedPath);
        }

        // Generar URL pública
        $url = Storage::disk($this->disk)->url($storedPath);

        return [
            'url' => $url,
            'path' => $storedPath,
            'filename' => basename($storedPath),
            'size' => $size,
            'mime_type' => $mimeType,
            'dimensions' => $dimensions,
        ];
    }

    /**
     * Elimina un archivo del storage
     *
     * @param string $path Path del archivo a eliminar
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
     * Elimina un archivo por URL
     *
     * @param string $url URL del archivo
     * @return bool
     */
    public function deleteByUrl(string $url): bool
    {
        return $this->delete($url);
    }

    /**
     * Valida que el modelo y campo existan en la configuración
     *
     * @param string $model
     * @param string $field
     * @return void
     * @throws \Exception
     */
    protected function validateModelAndField(string $model, string $field): void
    {
        $validations = config('uploads.validations', []);

        if (!isset($validations[$model])) {
            throw new \Exception("Model '{$model}' is not configured for file uploads.");
        }

        if (!isset($validations[$model][$field])) {
            throw new \Exception("Field '{$field}' is not configured for model '{$model}'.");
        }
    }

    /**
     * Genera el path del archivo según la estrategia configurada
     *
     * @param UploadedFile $file
     * @param string $model
     * @param string $field
     * @return string
     */
    protected function generatePath(UploadedFile $file, string $model, string $field): string
    {
        $extension = $file->getClientOriginalExtension();
        $strategy = config('uploads.naming_strategy', 'timestamp');

        switch ($strategy) {
            case 'uuid':
                $filename = Str::uuid() . '.' . $extension;
                break;
            case 'original':
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $extension;
                break;
            case 'timestamp':
            default:
                $filename = time() . '-' . Str::random(10) . '.' . $extension;
                break;
        }

        return "{$this->pathPrefix}/{$model}/{$field}/{$filename}";
    }

    /**
     * Obtiene las dimensiones de una imagen
     *
     * @param string $path
     * @return array{width: int, height: int}|null
     */
    protected function getImageDimensions(string $path): ?array
    {
        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            
            if (!file_exists($fullPath)) {
                return null;
            }

            // Usar getimagesize() nativo de PHP
            $imageInfo = @getimagesize($fullPath);
            
            if ($imageInfo === false) {
                return null;
            }
            
            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extrae el path del storage desde una URL
     *
     * @param string $url
     * @return string
     */
    protected function extractPathFromUrl(string $url): string
    {
        // Remover el dominio y obtener solo el path
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Remover /storage si existe
        $path = str_replace('/storage/', '', $path);
        $path = ltrim($path, '/');

        return $path;
    }

    /**
     * Obtiene las reglas de validación para un modelo y campo específicos
     *
     * @param string $model
     * @param string $field
     * @return array
     */
    public function getValidationRules(string $model, string $field): array
    {
        $validations = config('uploads.validations', []);

        if (!isset($validations[$model][$field])) {
            return ['file', 'max:2048']; // Reglas por defecto
        }

        return $validations[$model][$field]['rules'] ?? ['file', 'max:2048'];
    }

    /**
     * Obtiene los tipos MIME permitidos para un modelo y campo específicos
     *
     * @param string $model
     * @param string $field
     * @return array
     */
    public function getAllowedMimeTypes(string $model, string $field): array
    {
        $validations = config('uploads.validations', []);

        if (!isset($validations[$model][$field])) {
            return [];
        }

        return $validations[$model][$field]['mime_types'] ?? [];
    }
}

