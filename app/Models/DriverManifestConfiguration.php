<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverManifestConfiguration extends Model
{
    protected $fillable = [
        'task',
        'order_reference',
        'location_name',
        'location_address',
        'contact_number',
        'second_contact',
        'additional_instructions',
        'weight',
        'stop_time',
        'origin_window',
        'client_name',
        'service_level',
        'customer_signature',
        'location_postcode',
        'contact_person',
        'priority',
        'order_item',
        'volume',
        'web_ref',
        'vehicle_requirements',
        'location_instructions',
        'area_of_control',
        'distance',
        'territory'
    ];

    protected $casts = [
        'task' => 'integer',
        'order_reference' => 'integer',
        'location_name' => 'integer',
        'location_address' => 'integer',
        'contact_number' => 'integer',
        'second_contact' => 'integer',
        'additional_instructions' => 'integer',
        'weight' => 'integer',
        'stop_time' => 'integer',
        'origin_window' => 'integer',
        'client_name' => 'integer',
        'service_level' => 'integer',
        'customer_signature' => 'integer',
        'location_postcode' => 'integer',
        'contact_person' => 'integer',
        'priority' => 'integer',
        'order_item' => 'integer',
        'volume' => 'integer',
        'web_ref' => 'integer',
        'vehicle_requirements' => 'integer',
        'location_instructions' => 'integer',
        'area_of_control' => 'integer',
        'distance' => 'integer',
        'territory' => 'integer'
    ];
} 