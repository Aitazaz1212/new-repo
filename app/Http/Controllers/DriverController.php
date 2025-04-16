<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\{
    Driver,
    DriverDay,
    Vehicle,
    User,
    Role
};
use Carbon\Carbon;
use Illuminate\Http\{
    JsonResponse,
    Request
};
use Illuminate\Support\Facades\{
    DB,
    Validator,
    Log,
    Hash
};

/**
 * @tags Driver Management
 */

class DriverController extends Controller
{
    /**
     * Get All Drivers
     *
     * Retrieves all drivers with their related data including driver time, vehicle, warehouse, and territories.
     *
     * @authenticated
     *
     * @response {
     *   "drivers": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "territories": ["territory1", "territory2"],
     *     "driver_time": {
     *       "id": 1,
     *       "start_time": "09:00",
     *       "end_time": "17:00"
     *     },
     *     "vehicle": {
     *       "id": 1,
     *       "name": "Van 1"
     *     },
     *     "warehouse": {
     *       "id": 1,
     *       "name": "Main Warehouse"
     *     }
     *   }],
     *   "success": true
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated"
     * }
     */
    public function getDrivers(Request $request): JsonResponse
    {
        $drivers = Driver::with([
            'driverTime',
            'vehicle',
            'warehouse',
            'driverTerritory'
        ])
            ->get();

        $drivers->each(function ($driver) {
            $driver->territories = json_decode($driver->territories);
            unset($driver->pwd);
            // Rename uName to login
            $driver->login = $driver->uName;
            unset($driver->uName);
        });

        return response()->json([
            'drivers' => $drivers,
            'success' => true
        ], 200);
    }

    /**
     * Save Driver
     *
     * Creates a new driver with their schedule and territory assignments.
     *
     * @group Driver Management
     *
     * @bodyParam name string required The driver's full name. Example: John Doe
     * @bodyParam external_id string required External identifier for the driver. Example: DRV123
     * @bodyParam password string required Driver's password. No-example
     * @bodyParam cpassword string required Confirm password, must match password. No-example
     * @bodyParam distribution_centre integer required Distribution centre ID. Example: 1
     * @bodyParam number string optional Driver's identification number. Example: D001
     * @bodyParam comment string optional Additional notes about the driver. Example: Experienced in city routes
     * @bodyParam vehicle integer optional Assigned vehicle ID. Example: 1
     * @bodyParam cost_per_hour string optional Hourly cost rate. Example: 25.00
     * @bodyParam alerts_for_performers string optional Alert settings for the driver. Example: email,sms
     * @bodyParam territories array required List of territory assignments. Example: [{"key": 1}, {"key": 2}]
     * @bodyParam days array required Driver's schedule for each day.
     * @bodyParam days.*.day string required Day of week. Example: Monday
     * @bodyParam days.*.start_time string required Shift start time. Example: 09:00
     * @bodyParam days.*.end_time string required Shift end time. Example: 17:00
     * @bodyParam days.*.checked boolean required Is this day active. Example: true
     * @bodyParam days.*.start_day_time string required Day start time. Example: 08:30
     * @bodyParam days.*.end_day_time string required Day end time. Example: 17:30
     * @bodyParam days.*.start_driving_exactly_from_the_shift_start boolean required Start driving at shift start. Example: true
     *
     * @response 200 {
     *   "driver": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "external_id": "DRV123",
     *     "distribution_centre": 1,
     *     "vehicle": 1
     *   },
     *   "success": true
     * }
     *
     * @response 422 {
     *   "error": {
     *     "name": ["The name field is required."],
     *     "password": ["The password field is required."],
     *     "cpassword": ["The cpassword field must match password."],
     *     "distribution_centre": ["The distribution centre field is required."],
     *     "external_id": ["The external id field is required."]
     *   }
     * }
     *
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     **/
    public function saveDriver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'password' => 'required',
            'cpassword' => 'required|same:password',
            'distribution_centre' => 'required|integer',
            'external_id' => 'required',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {


            // Update vehicle assignments
            if ($request->vehicle) {
                $this->updateVehicleAssignments($request->vehicle);
            }

            // Create driver
            $driver = $this->createDriver($request);

            // Update vehicle with driver
            if ($request->vehicle) {
                $this->updateVehicleDriver($request->vehicle, $driver->id);
            }

            // Create territory assignments
            if (!empty($request->territories)) {
                $this->createTerritoryAssignments($driver->id, $request->territories);
            }

            // Create driver schedule
            if ($request->has('days')) {
                $this->createDriverSchedule($driver, $request->input('days'));
            }


            return response()->json(['driver' => $driver, 'success' => true], 200);
        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function updateVehicleAssignments(int $vehicleId): void
    {
        Driver::where('vehicle', $vehicleId)
            ->update(['vehicle' => null]);
    }

    private function createPerformer($driverId, $email, $login, $name, $password): void
    {
        // Create user is performer .
        $user = new User();
        $user->driver_id = $driverId;
        $user->name = $name;
        $user->username = $login;
        $user->email = $email;
        $user->language = 'en';
        $user->password = Hash::make($password);
        $user->save();
        //  Assign performer role to the  user .
        DB::table('role_user')->insert([
            'user_id' => $user->id,
            'role_id' => Role::where('slug', 'performer')->first()->id
        ]);
    }

    /**
     * Process time limits from request
     *
     * @param Request $request
     * @return array
     */
    private function processTimeLimits(Request $request): array
    {
        return [
            'driving_limit' => $request->driving_limit['time'] ?? '00:00',
            'duty_time_limit' => $request->duty_time_limit['time'] ?? '00:00',
            'run_duration_limit' => $request->run_duration_limit['time'] ?? '00:00',
            'allow_driving_limit' => $request->driving_limit['checkbox'] ?? false,
            'allow_duty_time_limit' => $request->duty_time_limit['checkbox'] ?? false,
            'allow_run_duration_limit' => $request->run_duration_limit['checkbox'] ?? false,
        ];
    }

    private function createDriver(Request $request): Driver
    {
        // Process time limits
        $timeLimits = $this->processTimeLimits($request);

        $driver = new Driver;
        $driver->fill([
            'name' => $request->name,
            'external_id' => $request->external_id,
            'pwd' => Hash::make($request->password),
            'distribution_centre' => $request->distribution_centre,
            'num' => $request->number,
            'comment' => $request->comment,
            'vehicle' => $request->vehicle,
            'cost_per_hour' => $request->cost_per_hour,
            'alerts_for_performers' => $request->alerts_for_performers,
            'territories' => json_encode($request->territories),
            'start_of_day_location' => $request->start_of_day_location,
            'end_of_day_location' => $request->end_of_day_location,
            'driving_limit' => $timeLimits['driving_limit'],
            'duty_time_limit' => $timeLimits['duty_time_limit'],
            'run_duration_limit' => $timeLimits['run_duration_limit'],
            'allow_driving_limit' => $timeLimits['allow_driving_limit'] ? 1 : 0,
            'allow_duty_time_limit' => $timeLimits['allow_duty_time_limit'] ? 1 : 0,
            'allow_run_duration_limit' => $timeLimits['allow_run_duration_limit'] ? 1 : 0,
            'start_of_day_address' => $request->start_of_day_address,
            'end_of_day_address' => $request->end_of_day_address,
            'uName' => $request->login,
            'email' => $request->email,
        ]);
        $driver->save();

        /**
         *  Create user for driver we treat this user  is a performer.
         */
        $this->createPerformer($driver->id, $request->email, $request->login, $request->name, $request->password);

        return $driver;
    }

    private function updateVehicleDriver(int $vehicleId, int $driverId): void
    {
        Vehicle::find($vehicleId)?->update(['driver_id' => $driverId]);
    }

    private function createTerritoryAssignments(int $driverId, array $territories): void
    {
        $assignments = collect($territories)->map(function ($territory) use ($driverId) {
            return [
                'territory_id' => $territory['key'],
                'driver_id' => $driverId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        DB::table('territory_driver')->insert($assignments);
    }

    private function createDriverSchedule(Driver $driver, array $days): void
    {
        // Create a local copy of the days array
        $daysData = collect($days)->map(function ($dayData) use ($driver) {
            return [
                'driver_id' => $driver->id,
                'name' => $driver->name,
                'day' => $dayData['day'],
                'start_time' => $dayData['start_time'],
                'checked' => $dayData['checked'] ? 1 : 0,
                'end_time' => $dayData['end_time'],
                'start_day' => $dayData['start_day_time'],
                'end_day' => $dayData['end_day_time'],
                'start_driving_exactly_from_the_shift_start' => $dayData['start_driving_exactly_from_the_shift_start'] ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        DB::table('driverDays')->insert($daysData);
    }

    /**
     * Update Driver
     *
     * Updates an existing driver's information, schedule, and territory assignments.
     *
     * @group Driver Management
     *
     * @bodyParam driver_id integer required The ID of the driver to update. Example: 1
     * @bodyParam name string required Driver's full name. Example: John Doe
     * @bodyParam external_id string required External identifier. Example: DRV123
     * @bodyParam number string optional Driver's identification number. Example: D001
     * @bodyParam id integer optional User ID. Example: 1
     * @bodyParam comment string optional Additional notes. Example: Experienced driver
     * @bodyParam vehicle integer optional Assigned vehicle ID. Example: 1
     * @bodyParam cost_per_hour string optional Hourly cost rate. Example: 25.00
     * @bodyParam alerts_for_performers string optional Alert settings. Example: email,sms
     * @bodyParam distribution_centre integer required Distribution centre ID. Example: 1
     * @bodyParam territories array required Territory assignments. Example: [{"key": 1}, {"key": 2}]
     * @bodyParam days array required Driver's schedule.
     * @bodyParam days.*.day string required Day of week. Example: Monday
     * @bodyParam days.*.start_time string required Shift start time. Example: 09:00
     * @bodyParam days.*.end_time string required Shift end time. Example: 17:00
     * @bodyParam days.*.checked boolean required Is day active. Example: true
     * @bodyParam days.*.start_day_time string required Day start time. Example: 08:30
     * @bodyParam days.*.end_day_time string required Day end time. Example: 17:30
     * @bodyParam days.*.start_driving_exactly_from_the_shift_start boolean required Start driving at shift start. Example: true
     *
     * @response 200 {
     *   "driver": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "external_id": "DRV123",
     *     "driverTime": [{
     *       "id": 1,
     *       "day": "Monday",
     *       "start_time": "09:00",
     *       "end_time": "17:00"
     *     }]
     *   },
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Driver not found"
     * }
     */
    public function updateDriver(Request $request): JsonResponse
    {
        $driver = Driver::find($request->driver_id);

        if (!$driver) {
            return response()->json('Driver not found', 422);
        }

        // Validate email uniqueness before updating
        $validator = Validator::make($request->all(), [
            'email' => [
                'email',
                Rule::unique('drivers')->ignore($driver->id)
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }


        try {
            // Process time limits - use the same method as in createDriver
            $timeLimits = $this->processTimeLimits($request);

            // Update driver information
            $driver->fill([
                'name' => $request->name ?? $driver->name,
                'num' => $request->number ?? $driver->num,
                'user_id' => $request->id ?? $driver->user_id,
                'comment' => $request->comment ?? $driver->comment,
                'external_id' => $request->external_id ?? $driver->external_id,
                'vehicle' => $request->vehicle ?? $driver->vehicle,
                'cost_per_hour' => $request->cost_per_hour ?? $driver->cost_per_hour,
                'alerts_for_performers' => $request->alerts_for_performers ?? $driver->alerts_for_performers,
                'distribution_centre' => $request->distribution_centre ?? $driver->distribution_centre,
                'territories' => json_encode($request->territories) ?? $driver->territories,
                'start_of_day_location' => $request->start_of_day_location ?? $driver->start_of_day_location,
                'end_of_day_location' => $request->end_of_day_location ?? $driver->end_of_day_location,
                'start_of_day_address' => $request->start_of_day_address ?? $driver->start_of_day_address,
                'end_of_day_address' => $request->end_of_day_address ?? $driver->end_of_day_address,
                'driving_limit' => $timeLimits['driving_limit'],
                'duty_time_limit' => $timeLimits['duty_time_limit'],
                'run_duration_limit' => $timeLimits['run_duration_limit'],
                'allow_driving_limit' => $timeLimits['allow_driving_limit'] ? 1 : 0,
                'allow_duty_time_limit' => $timeLimits['allow_duty_time_limit'] ? 1 : 0,
                'allow_run_duration_limit' => $timeLimits['allow_run_duration_limit'] ? 1 : 0,
                'uName' => $request->login ?? $driver->uName,
                'pwd' => $request->password ? Hash::make($request->password) : $driver->pwd,
                'email' => $request->email ?? $driver->email,
            ]);
            $driver->save();

            // Update vehicle assignments
            if ($request->vehicle) {
                $this->updateVehicleAssignments($request->vehicle);
                $this->updateVehicleDriver($request->vehicle, $driver->id);
            }

            // Update driver schedule
            if ($request->has('days') && is_array($request->days)) {
                $this->updateDriverSchedule($driver, $request->days);
            }

            // update the dirver if  driver  exist the performer record in the user table .
            $this->updatePerformer($request->name, $request->login, $request->email, $request->password, $driver->id, $driver);



            // Return updated driver with schedule for the last updated day
            $days = collect($request->days)->toArray();
            $lastDay = !empty($days) ? end($days)['day'] : null;
            $driverWithDays = Driver::with(['driverTime' => function ($query) use ($lastDay) {
                $query->where('day', $lastDay);
            }])->find($request->driver_id);

            return response()->json(['driver' => $driverWithDays, 'success' => true]);
        } catch (\Exception $e) {

            Log::error($e);
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update user table if the driver  exist performer is performer.
     */
    private function updatePerformer($name, $login, $email, $password, $driverId, $driver = null): void
    {
        $user =  User::where('driver_id', $driverId)->first();

        if ($user) {
            $user->name = $driver ? $driver->name : $name;
            $user->username = $driver ? $driver->uName : $login;
            $user->email = $driver ? $driver->email : $email;
            $user->password = $driver ? $driver->pwd : Hash::make($password);
            $user->save();
        }
    }

    /**
     * Update driver's schedule for each day
     */
    private function updateDriverSchedule(Driver $driver, array $days): void
    {
        foreach ($days as $day) {
            DriverDay::updateOrCreate(
                [
                    'driver_id' => $driver->id,
                    'day' => $day['day']
                ],
                [
                    'name' => $driver->name,
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                    'start_day' => $day['start_day_time'],
                    'end_day' => $day['end_day_time'],
                    'checked' => $day['checked'] ? 1 : 0,
                    'start_driving_exactly_from_the_shift_start' => $day['start_driving_exactly_from_the_shift_start'] ? 1 : 0
                ]
            );
        }
    }

    /**
     * Delete Drivers
     *
     * Deletes multiple drivers and their associated records.
     *
     * @group Driver Management
     *
     * @bodyParam id array required Array of driver IDs to delete. Example: [1, 2, 3]
     *
     * @response {
     *   "message": "Selected drivers deleted successfully",
     *   "success": true
     * }
     *
     * @response 422 {
     *   "error": "No drivers selected for deletion"
     * }
     *
     * @response 500 {
     *   "error": "Something went wrong"
     * }
     */
    public function deleteDrivers(Request $request): JsonResponse
    {
        if (empty($request->id)) {
            return response()->json(['error' => 'No drivers selected for deletion'], 422);
        }

        try {


            $driverIds = $request->id;

            // Delete related records in driver_days table
            DriverDay::whereIn('driver_id', $driverIds)->delete();

            // Delete territory assignments
            DB::table('territory_driver')
                ->whereIn('driver_id', $driverIds)
                ->delete();

            // Reset vehicle assignments
            Vehicle::whereIn('driver_id', $driverIds)
                ->update(['driver_id' => null]);

            // Delete records in drivers table
            Driver::whereIn('id', $driverIds)->delete();

            // Delete records in  user  table
            User::whereIn('driver_id', $driverIds)->delete();



            return response()->json([
                'message' => 'Selected drivers deleted successfully',
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }


    /**
     * Search Drivers
     *
     * Search for drivers by name, number, or external ID.
     *
     * @group Driver Management
     *
     * @queryParam driver string required Search term for driver name, number or external ID. Example: John
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "num": "D001",
     *     "external_id": "DRV123",
     *     "driverTime": [{
     *       "id": 1,
     *       "day": "Monday",
     *       "start_time": "09:00",
     *       "end_time": "17:00"
     *     }],
     *     "wareHouse": {
     *       "id": 1,
     *       "name": "Main Warehouse"
     *     },
     *     "vehicle": {
     *       "id": 1,
     *       "name": "Vehicle 1"
     *     }
     *   }]
     * }
     *
     * @response 422 {
     *   "error": "Search term is required"
     * }
     */
    public function searchDrivers(Request $request): JsonResponse
    {
        if (!$request->has('driver')) {
            return response()->json(['error' => 'Search term is required'], 422);
        }

        $searchTerm = $request->query('driver');

        $drivers = Driver::where('name', 'like', "%{$searchTerm}%")
            ->orWhere('num', 'like', "%{$searchTerm}%")
            ->orWhere('external_id', 'like', "%{$searchTerm}%")
            ->with([
                'driverTime',
                'wareHouse',
                'vehicle'
            ])
            ->get();

        //convert the  uName to login  of all driver
        $drivers->each(function ($driver) {
            unset($driver->pwd);
            // Rename uName to login of each driver
            $driver->login = $driver->uName;
            unset($driver->uName);
        });


        return response()->json($drivers, 200);
    }

    /**
     * Filter Drivers
     *
     * Filter drivers by distribution centre and vehicle.
     *
     * @group Driver Management
     *
     * @bodyParam distributionCentre array optional Array of distribution centre IDs to filter by. Example: [1, 2]
     * @bodyParam vehicle_id array optional Array of vehicle IDs to filter by. Example: [1, 2]
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "external_id": "DRV123",
     *     "distribution_centre": 1,
     *     "vehicle": 1,
     *     "driverTime": [{
     *       "id": 1,
     *       "day": "Monday",
     *       "start_time": "09:00",
     *       "end_time": "17:00"
     *     }],
     *     "wareHouse": {
     *       "id": 1,
     *       "name": "Main Warehouse"
     *     },
     *     "vehicle": {
     *       "id": 1,
     *       "name": "Vehicle 1"
     *     }
     *   }]
     * }
     *
     * @response 200 []
     */
    public function filterDrivers(Request $request): JsonResponse
    {
        $query = Driver::query();

        if (!empty($request->distributionCentre)) {
            $query->whereIn('distribution_centre', $request->distributionCentre);
        }

        if (!empty($request->vehicle_id)) {
            $query->whereIn('vehicle', $request->vehicle_id);
        }

        $drivers = $query->with([
            'driverTime',
            'wareHouse',
            'vehicle'
        ])->get();

        return response()->json($drivers, 200);
    }

    /**
     * Update Driver Start Time
     *
     * Updates a driver's start time for a specific day.
     *
     * @group Driver Management
     *
     * @bodyParam driver_id integer required The ID of the driver. Example: 1
     * @bodyParam day_name string required The day of the week. Example: Monday
     * @bodyParam start_time string required The new start time. Example: 09:00
     *
     * @response {
     *   "driver": {
     *     "id": 1,
     *     "driver_id": 1,
     *     "day": "Monday",
     *     "start_time": "09:00",
     *     "end_time": "17:00",
     *     "checked": 1,
     *     "name": "John Doe"
     *   },
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Driver not found"
     * }
     */
    public function updateDriverStartTime(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|integer|exists:drivers,id',
            'day_name' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $driver = DriverDay::where('driver_id', $request->driver_id)
                ->where('day', $request->day_name)
                ->first();

            if (!$driver) {
                return response()->json(['message' => 'Driver not found'], 422);
            }

            $driver->start_time = $request->start_time;
            $driver->save();

            return response()->json([
                'driver' => $driver,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Driver Performance Report
     *
     * Get driver performance metrics filtered by date range and distribution centre.
     *
     * @group Driver Management
     *
     * @bodyParam startDate string required Start date in d/m/Y format. Example: 01/01/2024
     * @bodyParam endDate string required End date in d/m/Y format. Example: 31/01/2024
     * @bodyParam distribution_centre integer required Distribution centre ID. Example: 1
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "vehicleName": "Vehicle 1",
     *     "averageTimeDeviation": "30",
     *     "correctSequence": "95",
     *     "OnTimeDeliveries": "90",
     *     "completedJobs": 150,
     *     "avgOperationTime": "45.5"
     *   }],
     *   "success": true
     * }
     *
     * @response 400 {
     *   "message": "The request is empty"
     * }
     */
    public function driverPerformance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date_format:d/m/Y',
            'endDate' => 'required|date_format:d/m/Y',
            'distribution_centre' => 'required|integer|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        if (empty($request->all())) {
            return response()->json(['message' => 'The request is empty'], 400);
        }

        try {
            $startDate = Carbon::createFromFormat('d/m/Y', $request->startDate)->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $request->endDate)->endOfDay();

            $report = DB::table('warehouses')
                ->where('warehouses.id', $request->distribution_centre)
                ->leftJoin('drivers', 'warehouses.id', '=', 'drivers.distribution_centre')
                ->join('vehicles', 'drivers.id', '=', 'vehicles.driver_id')
                ->leftJoin('disp', 'drivers.vehicle', '=', 'disp.van')
                ->leftJoin('orders', 'disp.oid', '=', 'orders.id')
                ->whereBetween('orders.deliverBy_datetime', [$startDate, $endDate])
                ->select([
                    'drivers.id',
                    'drivers.name',
                    'vehicles.name as vehicleName',
                    DB::raw('MAX(orders.locked) as averageTimeDeviation'),
                    DB::raw('MAX(orders.locked) as correctSequence'),
                    DB::raw('MAX(orders.locked) as OnTimeDeliveries'),
                    DB::raw('COUNT(orders.id) as completedJobs'),
                    DB::raw('COALESCE(SUM(orders.operation_duration) / NULLIF(COUNT(orders.id), 0), 0) as avgOperationTime')
                ])
                ->groupBy('drivers.id', 'drivers.name', 'vehicles.name')
                ->get();

            return response()->json([
                'data' => $report,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while generating the report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * On-Time Delivery Report
     *
     * Generates a report of driver delivery performance within a date range for a specific warehouse.
     *
     * @group Reports
     *
     * @bodyParam fromDate string required Start date in d/m/Y format. Example: 01/01/2024
     * @bodyParam toDate string required End date in d/m/Y format. Example: 31/01/2024
     * @bodyParam warehouseId integer required Warehouse ID to filter by. Example: 1
     *
     * @response {
     *   "driversData": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "vehicle": {
     *       "id": 1,
     *       "name": "Van 1",
     *       "disp": [{
     *         "id": 1,
     *         "order": {
     *           "id": 1,
     *           "deliverBy_datetime": "2024-01-01 14:00:00",
     *           "orderOpertionTimeWindow": {...},
     *           "customer": {...}
     *         }
     *       }]
     *     }
     *   }],
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "fromDate": ["The from date field is required."]
     *   }
     * }
     */
    public function onTimeReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fromDate' => 'required|date_format:d/m/Y',
            'toDate' => [
                'required',
                'date_format:d/m/Y',
                'after_or_equal:fromDate'
            ],
            'warehouseId' => 'required|integer|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fromDate = Carbon::createFromFormat('d/m/Y', $request->fromDate)->format('Y-m-d');
            $toDate = Carbon::createFromFormat('d/m/Y', $request->toDate)->format('Y-m-d');
            $warehouseId = $request->warehouseId;

            $results = Driver::with([
                'vehicle' => function ($query) use ($fromDate, $toDate, $warehouseId) {
                    $query->with([
                        'disp' => function ($query) use ($fromDate, $toDate, $warehouseId) {
                            $query->with([
                                'order' => function ($query) use ($fromDate, $toDate, $warehouseId) {
                                    $query->with(['orderOperationTimeWindow', 'customer'])
                                        ->whereBetween('deliverBy_datetime', [$fromDate, $toDate])
                                        ->when($warehouseId, function ($query) use ($warehouseId) {
                                            $query->where('warehouse_id', $warehouseId);
                                        });
                                }
                            ]);
                        }
                    ]);
                }
            ])->get();

            return response()->json([
                'driversData' => $results,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while processing your request.',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Daily Run Report
     *
     * Get daily run information for vehicles, drivers, and routes by date and distribution centre.
     *
     * @group Driver Management
     *
     * @bodyParam date string required Date in d/m/Y format. Example: 01/01/2024
     * @bodyParam distributionId integer optional Distribution centre ID to filter by. Example: 1
     *
     * @response {
     *   "data": [{
     *     "id": 1,
     *     "driver": {
     *       "id": 1,
     *       "name": "John Doe"
     *     },
     *     "vehicle": {
     *       "id": 1,
     *       "name": "Van 1"
     *     },
     *     "dispatchedRoute": {
     *       "id": 1,
     *       "date": "01-Jan-2024",
     *       "start_time": "09:00",
     *       "end_time": "17:00",
     *       "exp_delivery_time": "16:00",
     *       "status": "Completed",
     *       "route": "R001",
     *       "to_collect": "100.00",
     *       "payments": "100.00"
     *     }
     *   }],
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "date": ["The date field is required."]
     *   }
     * }
     */
    public function dailyRun(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:d/m/Y',
            'distributionId' => 'nullable|integer|exists:warehouses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $formattedDate = Carbon::createFromFormat('d/m/Y', $request->date)
                ->format('d-M-Y');

            $query = Vehicle::with([
                'driver',
                'warehouse',
                'dispatchedRoutes' => function ($query) use ($formattedDate) {
                    $query->where('date', $formattedDate)
                        ->with(['disp.order']);
                }
            ])

                /**
                 *  Add the wherehas condition  on dispatchedRoutes , that will only fetch the
                 * reuqested date routes otherwise in the above with  dispatchedRoutes  all dates
                 * routes . our date conditon is not works.
                 */
                ->whereHas('dispatchedRoutes', function ($query) use ($formattedDate) {
                    $query->where('date', $formattedDate);
                })
                ->when($request->filled('distributionId'), function ($query) use ($request) {
                    $query->where('distribution_centre_id', $request->distributionId);
                });

            $data = $query->get();

            return response()->json([
                'data' => $data,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Driver On-Time Report
     *
     * Get driver delivery performance metrics within a date range.
     *
     * @deprecated Use onTimeReport() instead. This method will be removed in future versions.
     * @group Driver Management
     *
     * @bodyParam fromDate string required Start date in d/m/Y format. Example: 01/01/2024
     * @bodyParam toDate string required End date in d/m/Y format. Example: 31/01/2024
     * @bodyParam warehouseId integer optional Warehouse ID to filter by. Example: 1
     *
     * @response {
     *   "driversData": [{
     *     "id": 1,
     *     "name": "John Doe",
     *     "vehicle": {
     *       "id": 1,
     *       "name": "Van 1",
     *       "disp": [{
     *         "id": 1,
     *         "order": {
     *           "id": 1,
     *           "deliverBy_datetime": "2024-01-01 14:00:00",
     *           "orderOpertionTimeWindow": {
     *             "date": "2024-01-01",
     *             "start_time": "09:00",
     *             "end_time": "17:00"
     *           },
     *           "customer": {...}
     *         }
     *       }]
     *     }
     *   }],
     *   "success": true
     * }
     *
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "fromDate": ["The from date field is required."]
     *   },
     *   "success": false
     * }
     */
    public function driverOnTimeReport(Request $request): JsonResponse
    {
        // Since onTimeReport already has the same functionality,
        // we'll reuse it instead of duplicating code
        return $this->onTimeReport($request);
    }

    public function updateDriverUsernames(): JsonResponse
    {
        try {


            $drivers = Driver::whereNotNull('name')->get();
            $count = 0;

            foreach ($drivers as $driver) {
                $driver->pwd = Hash::make($driver->pwd);
                $driver->uName = 'driver.' . $driver->name;
                $driver->save();
                $count++;
            }



            return response()->json([
                'message' => "Successfully updated {$count} driver usernames",
                'success' => true
            ], 200);
        } catch (\Exception $e) {

            Log::error('Failed to update driver usernames: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update driver usernames',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    // unllocate driver from a vehicle and reassign to the new vehicle.
    public function unAllocatedDriver(Request $request): JsonResponse
    {
        $validator = Validator::make($request->input('data', []), [
            'driver_id' => 'required|integer',
            'vehicle_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {


            // Extract data from the payload
            $driver_id = $request->input('data.driver_id');
            $vehicle_id = $request->input('data.vehicle_id');

            // Remove the driver from their current vehicle
            Vehicle::where('driver_id', $driver_id)
                ->update(['driver_id' => null]);

            // Assign the driver to the specified vehicle
            Vehicle::where('id', $vehicle_id)
                ->update(['driver_id' => $driver_id]);



            return response()->json([
                'message' => 'Driver allocation updated successfully.',
                'success' => true,
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
