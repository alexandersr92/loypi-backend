<?php

namespace App\Traits;

use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;

trait HasFileUploads
{
    /**
     * Boot del trait - registra eventos para eliminar archivos automáticamente
     */
    protected static function bootHasFileUploads(): void
    {
        // Eliminar archivos cuando se actualiza el modelo
        static::updating(function ($model) {
            $model->deleteOldFiles();
        });

        // Eliminar archivos cuando se elimina el modelo
        static::deleting(function ($model) {
            $model->deleteAllFiles();
        });
    }

    /**
     * Obtiene los campos que contienen archivos para este modelo
     * Debe ser sobrescrito en cada modelo que use el trait
     *
     * @return array<string>
     */
    protected function getFileFields(): array
    {
        return [];
    }

    /**
     * Elimina los archivos antiguos cuando se actualiza un campo de archivo
     */
    protected function deleteOldFiles(): void
    {
        $fileFields = $this->getFileFields();
        $uploadService = app(FileUploadService::class);

        foreach ($fileFields as $field) {
            $oldValue = $this->getOriginal($field);
            $newValue = $this->getAttribute($field);

            // Si el valor cambió y el valor anterior existe, eliminar el archivo anterior
            if ($oldValue && $oldValue !== $newValue && !empty($oldValue)) {
                try {
                    $uploadService->deleteByUrl($oldValue);
                } catch (\Exception $e) {
                    // Log el error pero no fallar la actualización
                    Log::warning("Error al eliminar archivo antiguo: {$e->getMessage()}", [
                        'model' => get_class($this),
                        'field' => $field,
                        'url' => $oldValue,
                    ]);
                }
            }
        }
    }

    /**
     * Elimina todos los archivos asociados al modelo
     */
    protected function deleteAllFiles(): void
    {
        $fileFields = $this->getFileFields();
        $uploadService = app(FileUploadService::class);

        foreach ($fileFields as $field) {
            $value = $this->getAttribute($field);

            if ($value && !empty($value)) {
                try {
                    $uploadService->deleteByUrl($value);
                } catch (\Exception $e) {
                    // Log el error pero no fallar la eliminación
                    Log::warning("Error al eliminar archivo: {$e->getMessage()}", [
                        'model' => get_class($this),
                        'field' => $field,
                        'url' => $value,
                    ]);
                }
            }
        }
    }
}

