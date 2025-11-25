<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UploadFileRequest;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group 📁 File Uploads
 * 
 * Endpoint para subir y eliminar archivos. Requiere autenticación.
 * 
 * @authenticated
 * @header Authorization Bearer {user_token}
 */
class UploadController extends Controller
{
    protected FileUploadService $uploadService;

    public function __construct(FileUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Subir un archivo
     * 
     * Sube un archivo y retorna la URL pública que debe guardarse en el campo correspondiente del modelo.
     * 
     * @bodyParam file file required El archivo a subir
     * @bodyParam model string required El modelo al que pertenece el archivo (campaign, business, user, reward)
     * @bodyParam field string required El campo del modelo (banner, logo, avatar, cover_image, logo_url, image_url)
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "url": "https://app.com/storage/uploads/campaigns/banner/1703123456-a1b2c3.png",
     *     "path": "uploads/campaigns/banner/1703123456-a1b2c3.png",
     *     "filename": "banner-1703123456-a1b2c3.png",
     *     "size": 1024000,
     *     "mime_type": "image/png",
     *     "dimensions": {
     *       "width": 1200,
     *       "height": 600
     *     }
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "file": ["El archivo es requerido."]
     *   }
     * }
     */
    public function upload(UploadFileRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $model = $request->input('model');
            $field = $request->input('field');

            $result = $this->uploadService->upload($file, $model, $field);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar un archivo
     * 
     * Elimina un archivo del storage usando su URL o path.
     * 
     * @bodyParam url string required La URL o path del archivo a eliminar
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Archivo eliminado correctamente"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Archivo no encontrado"
     * }
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string'],
        ], [
            'url.required' => 'La URL del archivo es requerida.',
            'url.string' => 'La URL debe ser una cadena de texto.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $url = $request->input('url');
            $deleted = $this->uploadService->deleteByUrl($url);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo eliminado correctamente',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Archivo no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

