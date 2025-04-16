<?php

namespace App\Http\Controllers;

use App\Models\ImportExportSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @tags Import Export Settings Management
 */
class ImportExportSettingController extends Controller
{
    /**
     * Get Import Export Settings
     * 
     * Retrieve import export configuration settings.
     * 
     * @group Import Export Settings Management
     * 
     * @response {
     *   "importExportSetting": {
     *     "id": 1,
     *     "file_format": "csv",
     *     "separator": ",",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "importExportSetting": "not found",
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch import export settings",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getImportExportSetting(): JsonResponse
    {
        try {
            $importExportSetting = ImportExportSetting::find(1);

            if (!$importExportSetting) {
                return response()->json([
                    'importExportSetting' => 'Import Export Setting not found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'importExportSetting' => $importExportSetting,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch import export settings',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Import Export Settings
     * 
     * Create or update import export configuration settings.
     * 
     * @group Import Export Settings Management
     * 
     * @bodyParam file_format string The file format for import/export. Example: "csv"
     * @bodyParam separator string The separator character for import/export. Example: ","
     * 
     * @response {
     *   "importExportSetting": {
     *     "id": 1,
     *     "file_format": "csv",
     *     "separator": ",",
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "file_format": ["The file format field is required."]
     *   },
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to create/update import export settings",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function createOrUpdateImportExportSetting(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file_format' => 'nullable|string',
                'separator' => 'nullable|string'
            ]);



            $importExportSetting = ImportExportSetting::find(1);
            if (!$importExportSetting) {
                $importExportSetting = new ImportExportSetting();
            }

            $importExportSetting->fill($validated);
            $importExportSetting->save();



            return response()->json([
                'importExportSetting' => $importExportSetting,
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to create/update import export settings',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
