<?php

namespace App\Http\Controllers;

use App\Models\AboutCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Company Management
 */
class AboutCompanyController extends Controller
{
    /**
     * Create or Update Company Information
     * 
     * Create or update company details including contact information.
     * 
     * @group Company Management
     * 
     * @bodyParam id integer The ID of the company to update. Example: 1
     * @bodyParam global_id string Unique global identifier. Example: "cbdjsjxjhdscsehcwscui"
     * @bodyParam account_name string The name of the company. Example: "Acme Corp"
     * @bodyParam account_logo string The logo URL of the company. Example: "https://example.com/logo.png"
     * @bodyParam street_address string The street address. Example: "123 Main St"
     * @bodyParam city string The city name. Example: "New York"
     * @bodyParam state string The state name. Example: "NY"
     * @bodyParam country string The country name. Example: "USA"
     * @bodyParam postcode string The postal code. Example: "10001"
     * @bodyParam ph string The phone number. Example: "+1234567890"
     * @bodyParam email string The email address. Example: "contact@acme.com"
     * @bodyParam contact_person string The contact person name. Example: "John Doe"
     * @bodyParam contact_position string The contact person position. Example: "Manager"
     * @bodyParam contact_details json The contact details object. Example: {"phone": "+1234567890", "email": "john@acme.com"}
     * 
     * @response {
     *   "aboutCompany": {
     *     "id": 1,
     *     "global_id": "cbdjsjxjhdscsehcwscui",
     *     "account_name": "Acme Corp",
     *     "account_logo": "https://example.com/logo.png",
     *     "street_address": "123 Main St",
     *     "city": "New York",
     *     "state": "NY",
     *     "country": "USA",
     *     "postcode": "10001",
     *     "ph": "+1234567890",
     *     "email": "contact@acme.com",
     *     "contact_person": "John Doe",
     *     "contact_position": "Manager",
     *     "contact_details": {"phone": "+1234567890", "email": "john@acme.com"},
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     */
    public function aboutCompany(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'nullable|integer|exists:about_companies,id',
                'global_id' => 'nullable|string|unique:about_companies,global_id,' . $request->id,
                'account_name' => 'nullable|string',
                'account_logo' => 'nullable|string',
                'street_address' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'country' => 'nullable|string',
                'postcode' => 'nullable|string',
                'ph' => 'nullable|string',
                'email' => 'nullable|email',
                'contact_person' => 'nullable|string',
                'contact_position' => 'nullable|string',
                'contact_details' => 'nullable'
            ]);



            $aboutCompany = AboutCompany::first();
            if (!$aboutCompany) {
                $aboutCompany = new AboutCompany();
            }

            $aboutCompany->fill($validated);

            // Handle contact details separately as it needs JSON encoding
            if ($request->has('contact_details')) {
                if (is_array($request->contact_details)) {
                    $contactDetails = json_encode($request->contact_details);
                } else {
                    $contactDetails = $request->contact_details;
                }
                $aboutCompany->contact_details = $contactDetails;
            }

            $aboutCompany->save();



            // Decode contact details for response
            if ($aboutCompany->contact_details) {
                $aboutCompany->contact_details = json_decode($aboutCompany->contact_details, true);
            }

            return response()->json([
                'aboutCompany' => $aboutCompany,
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
                'message' => 'Failed to save company information',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Get Company Information
     * 
     * Retrieve company details including contact information.
     * 
     * @group Company Management
     * 
     * @response {
     *   "aboutCompany": [{
     *     "id": 1,
     *     "global_id": "cbdjsjxjhdscsehcwscui",
     *     "account_name": "Acme Corp",
     *     "account_logo": "https://example.com/logo.png",
     *     "street_address": "123 Main St",
     *     "city": "New York",
     *     "state": "NY",
     *     "country": "USA",
     *     "postcode": "10001",
     *     "ph": "+1234567890",
     *     "email": "contact@acme.com",
     *     "contact_person": "John Doe",
     *     "contact_position": "Manager",
     *     "contact_details": {"phone": "+1234567890", "email": "john@acme.com"},
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   }],
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "No company information found",
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to fetch company information",
     *   "error": "Database error",
     *   "success": false
     * }
     */
    public function getAboutCompany(): JsonResponse
    {
        try {
            $aboutCompany = AboutCompany::first();

            if (!$aboutCompany) {
                return response()->json([
                    'message' => 'No company information found',
                    'success' => false,
                ], 200);
            }

            // Decode contact details for each company
            $aboutCompany->each(function ($company) {
                if ($company->contact_details) {
                    $company->contact_details = json_decode($company->contact_details, true);
                }
            });

            return response()->json([
                'aboutCompany' => $aboutCompany,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch company information',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
