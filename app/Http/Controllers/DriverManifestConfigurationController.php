<?php

namespace App\Http\Controllers;

use App\Models\DriverManifestConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * @tags Driver Manifest Configuration Management
 */
class DriverManifestConfigurationController extends Controller
{
    /**
     * Get Driver Manifest Configuration
     * 
     * Retrieve driver manifest configuration settings.
     * 
     * @group Driver Manifest Configuration Management
     * 
     * @response {
     *   "driverManifestConfiguration": {
     *     "id": 1,
     *     "task": false,
     *     "order_reference": false,
     *     "location_name": false,
     *     "location_address": false,
     *     "contact_number": false,
     *     "second_contact": false,
     *     "additional_instructions": false,
     *     "weight": false,
     *     "stop_time": false,
     *     "origin_window": false,
     *     "client_name": false,
     *     "service_level": false,
     *     "customer_signature": false,
     *     "location_postcode": false,
     *     "contact_person": false,
     *     "priority": false,
     *     "order_item": false,
     *     "volume": false,
     *     "web_ref": false,
     *     "vehicle_requirements": false,
     *     "location_instructions": false,
     *     "area_of_control": false,
     *     "distance": false,
     *     "territory": false,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "driverManifestConfiguration": "not found",
     *   "success": false
     * }
     */
    public function getExecutionDriverManifest(): JsonResponse
    {
        try {
            $executionDriverManifest = DriverManifestConfiguration::find(1);

            if (!$executionDriverManifest) {
                return response()->json([
                    'message' => 'Driver manifest configuration not found',
                    'success' => false
                ], 200);
            }

            return response()->json([
                'driverManifestConfiguration' => $executionDriverManifest,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch driver manifest configuration',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Create or Update Driver Manifest Configuration
     * 
     * Create or update driver manifest configuration settings.
     * 
     * @group Driver Manifest Configuration Management
     * 
     * @bodyParam task boolean The task visibility setting. Example: true
     * @bodyParam order_reference boolean The order reference visibility setting. Example: true
     * @bodyParam location_name boolean The location name visibility setting. Example: true
     * @bodyParam location_address boolean The location address visibility setting. Example: true
     * @bodyParam contact_number boolean The contact number visibility setting. Example: true
     * @bodyParam second_contact boolean The second contact visibility setting. Example: true
     * @bodyParam additional_instructions boolean The additional instructions visibility setting. Example: true
     * @bodyParam weight boolean The weight visibility setting. Example: true
     * @bodyParam stop_time boolean The stop time visibility setting. Example: true
     * @bodyParam origin_window boolean The origin window visibility setting. Example: true
     * @bodyParam client_name boolean The client name visibility setting. Example: true
     * @bodyParam service_level boolean The service level visibility setting. Example: true
     * @bodyParam customer_signature boolean The customer signature visibility setting. Example: true
     * @bodyParam location_postcode boolean The location postcode visibility setting. Example: true
     * @bodyParam contact_person boolean The contact person visibility setting. Example: true
     * @bodyParam priority boolean The priority visibility setting. Example: true
     * @bodyParam order_item boolean The order item visibility setting. Example: true
     * @bodyParam volume boolean The volume visibility setting. Example: true
     * @bodyParam web_ref boolean The web reference visibility setting. Example: true
     * @bodyParam vehicle_requirements boolean The vehicle requirements visibility setting. Example: true
     * @bodyParam location_instructions boolean The location instructions visibility setting. Example: true
     * @bodyParam area_of_control boolean The area of control visibility setting. Example: true
     * @bodyParam distance boolean The distance visibility setting. Example: true
     * @bodyParam territory boolean The territory visibility setting. Example: true
     * 
     * @response {
     *   "driverManifestConfiguration": {
     *     "id": 1,
     *     "task": true,
     *     "order_reference": true,
     *     "location_name": true,
     *     "location_address": true,
     *     "contact_number": true,
     *     "second_contact": true,
     *     "additional_instructions": true,
     *     "weight": true,
     *     "stop_time": true,
     *     "origin_window": true,
     *     "client_name": true,
     *     "service_level": true,
     *     "customer_signature": true,
     *     "location_postcode": true,
     *     "contact_person": true,
     *     "priority": true,
     *     "order_item": true,
     *     "volume": true,
     *     "web_ref": true,
     *     "vehicle_requirements": true,
     *     "location_instructions": true,
     *     "area_of_control": true,
     *     "distance": true,
     *     "territory": true,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     */
    public function createOrUpdateExecutionDriverManifest(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'task' => 'nullable|boolean',
                'order_reference' => 'nullable|boolean',
                'location_name' => 'nullable|boolean',
                'location_address' => 'nullable|boolean',
                'contact_number' => 'nullable|boolean',
                'second_contact' => 'nullable|boolean',
                'additional_instructions' => 'nullable|boolean',
                'weight' => 'nullable|boolean',
                'stop_time' => 'nullable|boolean',
                'origin_window' => 'nullable|boolean',
                'client_name' => 'nullable|boolean',
                'service_level' => 'nullable|boolean',
                'customer_signature' => 'nullable|boolean',
                'location_postcode' => 'nullable|boolean',
                'contact_person' => 'nullable|boolean',
                'priority' => 'nullable|boolean',
                'order_item' => 'nullable|boolean',
                'volume' => 'nullable|boolean',
                'web_ref' => 'nullable|boolean',
                'vehicle_requirements' => 'nullable|boolean',
                'location_instructions' => 'nullable|boolean',
                'area_of_control' => 'nullable|boolean',
                'distance' => 'nullable|boolean',
                'territory' => 'nullable|boolean'
            ]);



            $executionDriverManifest = DriverManifestConfiguration::find(1);
            if (!$executionDriverManifest) {
                $executionDriverManifest = new DriverManifestConfiguration();
            }

            $allKeys = array_keys($executionDriverManifest->getFillable());
            foreach ($allKeys as $key) {
                $executionDriverManifest->$key = isset($validated[$key]) ? true : false;
            }

            $executionDriverManifest->save();



            return response()->json([
                'driverManifestConfiguration' => $executionDriverManifest,
                'success' => true
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {

            Log::error('Failed to create/update driver manifest configuration: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create/update driver manifest configuration',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
