<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerLocationTimeWindow;
use App\Models\OperationDuration;
use App\Models\Order;
use App\Models\CustomerTimeWindow;

/**
 * @tags Customer Management
 */
class CustomerController extends Controller
{
    /**
     * Get Customer Locations
     * 
     * Retrieves a paginated list of customer locations with their details.
     * 
     * @group Customer Management
     * 
     * @queryParam per_page integer Number of items per page. Example: 10
     * @queryParam searchData string Search term for business name. Example: ACME Corp
     * 
     * @response {
     *   "customers": {
     *     "current_page": 1,
     *     "data": [{
     *       "id": 1,
     *       "userID": 1,
     *       "businessName": "ACME Corp",
     *       "client_name": "ACME Corporation",
     *       "email": "contact@acme.com",
     *       "secondEmail": "support@acme.com",
     *       "tel": "123-456-7890",
     *       "mob": "098-765-4321",
     *       "rate": "standard",
     *       "vehicle_requirements": "van",
     *       "preferred_drivers": ["John", "Jane"],
     *       "location": "123 Main St",
     *       "is_verified_location": true,
     *       "verified_when": "2024-01-01 12:00:00",
     *       "postcode": "12345",
     *       "description": "Main office location",
     *       "web": "www.acme.com",
     *       "location_refrence": "MAIN-01",
     *       "verified_by": "admin",
     *       "notificationByEmail": true,
     *       "notificationBySms": false,
     *       "customerTimeWindow": [{
     *         "id": 1,
     *         "start_time": "09:00",
     *         "end_time": "17:00"
     *       }],
     *       "operationDuration": {
     *         "duration": 30
     *       }
     *     }],
     *     "total": 100
     *   },
     *   "success": true
     * }
     */
    public function getCustomers(Request $request): JsonResponse
    {
        $query = Customer::query()
            ->when($request->query('searchData'), function ($query, $searchData) {
                $query->where('businessName', 'like', "%{$searchData}%");
            })
            ->select([
                'id',
                'userID',
                'businessName',
                'dBusinessName as client_name',
                'email1 as email',
                'email2 as secondEmail',
                'tel',
                'mob',
                'rate',
                'vehicle_requirements',
                'preferred_drivers',
                'street as location',
                'isAccount as is_verified_location',
                'timestamp as verified_when',
                'postcode',
                'description',
                'rate',
                'web',
                'location_refrence',
                'verified_by',
                'notificationByEmail',
                'notificationBySms',
            ])
            ->with(['customerTimeWindow', 'operationDuration']);

        $customers = $query->paginate($request->query('per_page', 15));

        // Transform JSON fields
        $customers->through(function ($customer) {
            $customer->preferred_drivers = $this->parseJsonField($customer->preferred_drivers);
            $customer->is_verified_location = $this->parseJsonField($customer->is_verified_location);
            return $customer;
        });

        return response()->json([
            'customers' => $customers,
            'success' => true
        ], 200);
    }

    /**
     * Save Customer Location
     * 
     * Creates a new customer location with time windows and operation durations.
     * 
     * @group Customer Management
     * 
     * @response {
     *   "customer": {
     *     "id": 1,
     *     "businessName": "ACME Corp",
     *     "client_name": "ACME Corporation"
     *   },
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     */
    public function saveCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'selectedTimes' => 'required|array',
            'selectedTimes.*.day' => 'required|string',
            'selectedTimes.*.start_time' => 'required|date_format:H:i',
            'selectedTimes.*.end_time' => 'required|date_format:H:i|after:selectedTimes.*.start_time',
            'preferredDrivers' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {


            $customer = Customer::create([
                'userID' => $request->user_id,
                'businessName' => $request->name,
                'dBusinessName' => $request->client_name,
                'mob' => $request->primary_telephone_number,
                'tel' => $request->secondary_telephone_number,
                'email1' => $request->email,
                'web' => $request->website,
                'rate' => $request->rate,
                'street' => $request->address,
                'postcode' => $request->postcode,
                'notificationByEmail' => $request->notificationByEmail,
                'notificationBySms' => $request->notificationBySms,
                'isAccount' => $request->verified,
                'verified_by' => $request->verified ? auth()->user()->name : '',
                'location_refrence' => $request->location_refrence,
                'description' => $request->description,
                'vehicle_requirements' => $request->vehicleRequirements,
                'preferred_drivers' => json_encode($request->preferredDrivers),
            ]);

            // Create operation duration
            OperationDuration::create([
                'customer_id' => $customer->id,
                'fixed_time_per_address' => $this->parseJsonField($request->fixed_time_per_address),
                'fixed_time_per_order' => $this->parseJsonField($request->fixed_time_per_order),
                'variable_time_per_capacity_delivery' => $this->parseJsonField($request->variable_time_per_capacity_delivery),
                'variable_time_per_capacity_collection' => $this->parseJsonField($request->variable_time_per_capacity_collection),
            ]);

            // Create time windows
            foreach ($request->selectedTimes as $timeWindow) {
                CustomerTimeWindow::create([
                    'customer_location_id' => $customer->id,
                    'day' => $timeWindow['day'],
                    'start_time' => $timeWindow['start_time'],
                    'end_time' => $timeWindow['end_time']
                ]);
            }



            return response()->json([
                'customer' => $customer,
                'success' => true
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse JSON field if it's a string
     */
    private function parseJsonField($field)
    {
        return is_string($field) ? json_decode($field) : $field;
    }

    /**
     * Delete Customer Locations
     * 
     * Deletes one or more customer locations and their associated data.
     * 
     * @group Customer Management
     * 
     * @response {
     *   "message": "Selected customer location deleted successfully",
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Customer locations not found",
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Something went wrong",
     *   "success": false
     * }
     */
    public function deleteCustomer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id' => 'required|array'
            ]);



            $locationIds = $request->id;

            // Verify customers exist
            $customersExist = Customer::whereIn('id', $locationIds)->exists();
            if (!$customersExist) {
                return response()->json([
                    'message' => 'Customer locations not found',
                    'success' => false
                ], 200);
            }

            // Delete associated time windows
            CustomerLocationTimeWindow::whereIn('customer_location_id', $locationIds)->delete();

            // Delete operation durations
            OperationDuration::whereIn('customer_id', $locationIds)->delete();

            // Delete customers
            Customer::whereIn('id', $locationIds)->delete();



            return response()->json([
                'message' => 'Selected customer location deleted successfully',
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Something went wrong',
                'success' => false
            ], 500);
        }
    }

    /**
     * Update Customer Location
     * 
     * Updates an existing customer location with time windows and operation durations.
     * 
     * @group Customer Management
     * 
     * @param customer_id integer required The ID of the customer to update. Example: 1
     * @param user_id integer optional User ID associated with the customer. Example: 1
     * @param businessName string required Business name of the customer. Example: ACME Corp
     * @param client_name string optional Display name of the customer. Example: ACME Corporation
     * @param mob string optional Mobile number. Example: +1234567890
     * @param tel string optional Telephone number. Example: +0987654321
     * @param email string optional Email address. Example: contact@acme.com
     * @param web string optional Website URL. Example: www.acme.com
     * @param address string optional Street address. Example: 123 Main St
     * @param location string optional Location description. Example: Main Building
     * @param postcode string optional Postal code. Example: 12345
     * @param notificationByEmail boolean optional Enable email notifications. Example: true
     * @param notificationBySms boolean optional Enable SMS notifications. Example: false
     * @param verified boolean optional Location verification status. Example: true
     * @param location_refrence string optional Location reference code. Example: MAIN-01
     * @param description string optional Location description. Example: Main office
     * @param vehicleRequirements string optional Required vehicle type. Example: van
     * @param rate integer optional Customer rate. Example: 1
     * @param preferredDrivers array optional List of preferred driver names. Example: ["John", "Jane"]
     * @param selectedTimes array required Array of time windows. Example: [{"day": "Monday", "start_time": "09:00", "end_time": "17:00"}]
     * @param fixed_time_per_address integer optional Fixed time per address. Example: 30
     * @param fixed_time_per_order integer optional Fixed time per order. Example: 45
     * @param variable_time_per_capacity_delivery integer optional Variable delivery time. Example: 15
     * @param variable_time_per_capacity_collection integer optional Variable collection time. Example: 15
     * 
     * @response {
     *   "status": 200,
     *   "customer": {
     *     "id": 1,
     *     "businessName": "ACME Corp",
     *     "customerTimeWindow": [{
     *       "day": "Monday",
     *       "start_time": "09:00",
     *       "end_time": "17:00"
     *     }],
     *     "operationDuration": {
     *       "fixed_time_per_address": 30
     *     }
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "Customer not found"
     * }
     * 
     * @response 500 {
     *   "status": 500,
     *   "error": "Error message",
     *   "success": false
     * }
     */
    public function updateCustomer(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'customer_id' => 'required|integer'
            ]);

            $customer = Customer::findOrFail($request->customer_id);



            // Update customer details
            $customer->update([
                'userID' => $request->user_id ?? $customer->userID,
                'businessName' => $request->businessName,
                'dBusinessName' => $request->client_name ?? $customer->dBusinessName,
                'mob' => $request->mob ?? $customer->mob,
                'tel' => $request->tel ?? $customer->tel,
                'email1' => $request->email ?? $customer->email1,
                'web' => $request->web ?? $customer->web,
                'street' => $request->address ?? $request->location ?? $customer->street,
                'postcode' => $request->postcode ?? $customer->postcode,
                'notificationByEmail' => $request->notificationByEmail ?? $customer->notificationByEmail,
                'notificationBySms' => $request->notificationBySms ?? $customer->notificationBySms,
                'isAccount' => $request->verified,
                'location_refrence' => $request->location_refrence ?? $customer->location_refrence,
                'description' => $request->description ?? $customer->description,
                'vehicle_requirements' => $request->vehicleRequirements ?? $customer->vehicle_requirements,
                'rate' => (int)($request->rate ?? $customer->rate),
                'preferred_drivers' => json_encode($request->preferredDrivers) ?? $customer->preferred_drivers,
                'verified_by' => $request->verified == "true" ? auth()->user()->name : "",
            ]);

            // Update operation duration
            OperationDuration::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'fixed_time_per_address' => $this->parseJsonField($request->fixed_time_per_address),
                    'fixed_time_per_order' => $this->parseJsonField($request->fixed_time_per_order),
                    'variable_time_per_capacity_delivery' => $this->parseJsonField($request->variable_time_per_capacity_delivery),
                    'variable_time_per_capacity_collection' => $this->parseJsonField($request->variable_time_per_capacity_collection),
                ]
            );

            // Update time windows
            if (!empty($request->selectedTimes)) {
                foreach ($request->selectedTimes as $timeWindow) {
                    CustomerLocationTimeWindow::updateOrCreate(
                        [
                            'customer_location_id' => $customer->id,
                            'day' => $timeWindow['day']
                        ],
                        [
                            'start_time' => $timeWindow['start_time'],
                            'end_time' => $timeWindow['end_time']
                        ]
                    );

                    // Delete time window if start and end times are same
                    if ($timeWindow['start_time'] === $timeWindow['end_time']) {
                        CustomerTimeWindow::where([
                            'customer_location_id' => $customer->id,
                            'day' => $timeWindow['day']
                        ])->delete();
                    }
                }
            }



            return response()->json([
                'status' => 200,
                'customer' => $customer->load('customerTimeWindow', 'operationDuration'),
                'success' => true
            ]);
        } catch (ModelNotFoundException $e) {

            return response()->json('Customer not found', 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => $e->getMessage(),
                'success' => false
            ]);
        }
    }

    /**
     * Search Customers
     * 
     * Search customers by business name, street address, or postcode.
     * 
     * @group Customer Management
     * 
     * @queryParam searchData string Search term for customer details. Example: ACME
     * 
     * @response {
     *   "status": 200,
     *   "data": [{
     *     "id": 1,
     *     "businessName": "ACME Corp",
     *     "street": "123 Main St",
     *     "postcode": "12345"
     *   }],
     *   "message": "Successfully fetched"
     * }
     * 
     * @response 500 {
     *   "status": 500,
     *   "error": "Error message"
     * }
     */
    public function customerSearch(Request $request): JsonResponse
    {
        try {
            $customerLocationInput = $request->query('searchData');

            $data = Customer::query()
                ->when($customerLocationInput, function ($query) use ($customerLocationInput) {
                    $query->where('businessName', 'like', "%{$customerLocationInput}%")
                        ->orWhere('street', 'like', "%{$customerLocationInput}%")
                        ->orWhere('postcode', 'like', "%{$customerLocationInput}%");
                })
                ->select(['id', 'businessName', 'street', 'postcode'])
                ->get();

            return response()->json([
                'status' => 200,
                'data' => $data,
                'message' => 'Successfully fetched'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Customer Locations
     * 
     * Retrieves customer locations with valid latitude and longitude coordinates.
     * 
     * @group Customer Management
     * 
     * @response {
     *   "data": [{
     *     "customer_id": 1,
     *     "businessName": "ACME Corp",
     *     "mob": "+1234567890",
     *     "email1": "contact@acme.com",
     *     "street": "123 Main St",
     *     "lat": "51.5074",
     *     "lng": "-0.1278",
     *     "postcode": "12345"
     *   }],
     *   "success": true
     * }
     * 
     * @response 500 {
     *   "error": "Something went wrong",
     *   "success": false
     * }
     */
    public function getCustomerLocation(): JsonResponse
    {
        try {
            $locations = Order::query()
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->where('lat', '!=', '')
                ->where('lng', '!=', '')
                ->join('customers', 'orders.cid', '=', 'customers.id')
                ->select([
                    'customers.id as customer_id',
                    'customers.businessName',
                    'customers.mob',
                    'customers.email1',
                    'street',
                    'orders.lat',
                    'orders.lng',
                    'customers.postcode'
                ])
                ->limit(100)
                ->get();

            return response()->json([
                'data' => $locations,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'success' => false
            ], 500);
        }
    }
}
