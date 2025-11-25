<?php

namespace App\Http\Requests\Api\V1;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controlador
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $model = $this->input('model');
        $field = $this->input('field');

        $baseRules = [
            'file' => ['required', 'file'],
            'model' => ['required', 'string'],
            'field' => ['required', 'string'],
        ];

        // Si se proporcionan model y field, agregar reglas específicas
        if ($model && $field) {
            $uploadService = app(FileUploadService::class);
            $specificRules = $uploadService->getValidationRules($model, $field);
            
            // Agregar validación de MIME types si están configurados
            $allowedMimeTypes = $uploadService->getAllowedMimeTypes($model, $field);
            if (!empty($allowedMimeTypes)) {
                // Convertir MIME types a extensiones para la validación mimes
                $extensions = [];
                foreach ($allowedMimeTypes as $mime) {
                    $exts = $this->mimeToExtensions($mime);
                    if ($exts) {
                        $extensions = array_merge($extensions, $exts);
                    }
                }
                if (!empty($extensions)) {
                    $specificRules[] = 'mimes:' . implode(',', array_unique($extensions));
                }
            }

            $baseRules['file'] = array_merge(['required', 'file'], $specificRules);
        }

        return $baseRules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es requerido.',
            'file.file' => 'El archivo debe ser válido.',
            'file.image' => 'El archivo debe ser una imagen.',
            'file.max' => 'El archivo es demasiado grande.',
            'file.dimensions' => 'Las dimensiones de la imagen no son válidas.',
            'file.mimes' => 'El archivo debe ser de tipo: :values.',
            'model.required' => 'El modelo es requerido.',
            'model.string' => 'El modelo debe ser una cadena de texto.',
            'field.required' => 'El campo es requerido.',
            'field.string' => 'El campo debe ser una cadena de texto.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $model = $this->input('model');
            $field = $this->input('field');

            if ($model && $field) {
                $uploadService = app(FileUploadService::class);
                
                try {
                    // Validar que el modelo y campo existan en la configuración
                    $uploadService->getValidationRules($model, $field);
                } catch (\Exception $e) {
                    $validator->errors()->add('model', $e->getMessage());
                }
            }
        });
    }

    /**
     * Convierte un MIME type a extensiones de archivo
     *
     * @param string $mime
     * @return array|null
     */
    protected function mimeToExtensions(string $mime): ?array
    {
        $mimeMap = [
            'image/jpeg' => ['jpeg', 'jpg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'image/svg+xml' => ['svg'],
            'image/gif' => ['gif'],
        ];

        return $mimeMap[$mime] ?? null;
    }
}

