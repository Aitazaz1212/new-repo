<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            // Primary and Foreign Keys
            $table->id();
            $table->integer('id_company')->default(0)->nullable();
            $table->integer('company_order_id')->nullable();
            $table->integer('id_status')->default(1)->nullable()
                ->comment('This should be \'id_order_status\'; the values are form the \'order_status\' table');
            $table->integer('cid')->nullable();

            // Basic Order Information
            $table->string('ref_no', 255)->nullable();
            $table->string('delRoute', 32)->nullable();
            $table->string('endDate', 32)->nullable();
            $table->string('endTime', 32)->nullable();
            $table->string('lastMaintainer', 48)->default('')->nullable();
            $table->integer('lastMaintainID')->nullable();
            $table->string('orderStatus', 24)->nullable();

            // Financial Information
            $table->double('paid')->default(0)->nullable();
            $table->double('total')->default(0)->nullable();
            $table->decimal('discount_amount', 9, 2)->default(0.00)->nullable();
            $table->decimal('discount_rate', 6, 2)->default(0.00)->nullable()
                ->comment('expressed as a percentage');
            $table->decimal('vat_paid', 6, 2)->default(0.00)->nullable()
                ->comment('expressed as a percentage');
            $table->double('vatAmount')->nullable();

            // Dates and Times
            $table->string('startDate', 24)->nullable();
            $table->string('startTime', 24)->nullable();
            $table->datetime('deliverBy_datetime')->nullable();

            // Staff Information
            $table->string('staffName', 48)->nullable();
            $table->integer('staffid')->nullable();

            // Delivery Information
            $table->string('dispatchType', 32)->default('Delivery')->nullable();
            $table->string('collector', 32)->nullable();
            $table->string('colDelDate', 24)->nullable();
            $table->string('carrier', 32)->nullable();

            // Order Type and Status
            $table->string('orderType', 32)->nullable();
            $table->integer('direct')->nullable();
            $table->string('paymentType', 32)->nullable();
            $table->integer('delOrder')->nullable();

            // Payment Information
            $table->string('authNo', 32)->nullable();
            $table->string('cardNo', 4)->nullable();

            // Company Information
            $table->string('companyName', 200)->nullable();
            $table->string('ouref', 400)->nullable();

            // Flags and Settings
            $table->boolean('proforma')->default(0);
            $table->boolean('showVat')->default(0)->nullable();
            $table->boolean('showNotes')->default(1)->nullable();

            // Service Information
            $table->string('serType', 100)->default('sale')->nullable();
            $table->string('icType', 100)->default('invoice')->nullable();
            $table->string('iNo', 100)->nullable();

            // E-commerce Information
            $table->string('ebayID', 64)->nullable();
            $table->string('ebayStatus', 100)->nullable();
            $table->string('amazonID', 100)->nullable();

            // Delivery Details
            $table->string('deliverBy', 100)->default('0');
            $table->string('delTime', 20)->nullable();
            $table->string('delStat', 20)->nullable();

            // Agent Information
            $table->text('agent')->nullable();
            $table->boolean('agentPaid')->default(0)->nullable();
            $table->double('agentOwed')->default(0)->nullable();
            $table->double('rate')->default(0)->nullable();

            // Additional Information
            $table->string('code', 64)->nullable();
            $table->string('parcelid', 20)->nullable();
            $table->string('neighbour', 400)->default('0')->nullable();
            $table->string('safePlace', 200)->default('0')->nullable();

            // Status Flags
            $table->boolean('aftersales')->default(0)->nullable();
            $table->boolean('done')->default(0)->nullable();
            $table->boolean('notLoaded')->default(0)->nullable();
            $table->boolean('amzShipped')->default(0)->nullable();
            $table->boolean('refund')->default(0)->nullable();

            // Collection Information
            $table->integer('colOrder')->default(0)->nullable();
            $table->string('colTime', 10)->nullable();
            $table->string('colRoute', 100)->nullable();

            // Location Information
            $table->string('lat', 100)->nullable();
            $table->string('lng', 100)->nullable();

            // Additional Flags and Settings

            $table->integer('tx')->default(0);
            $table->integer('txt')->default(0);
            $table->integer('p')->default(0);
            $table->integer('wes')->default(0);
            $table->string('cStatus', 100)->default('none');
            $table->string('extra', 10)->nullable();
            $table->string('beds', 255)->default('0');
            $table->integer('colID')->default(0);
            $table->integer('delID')->default(0);
            $table->integer('zeroVat')->default(0);
            $table->string('ebay', 100)->default('0');
            $table->string('postLoc', 50)->default('0');
            $table->integer('post')->default(0);
            $table->string('sender', 200)->default('0');
            $table->string('stamp', 100)->nullable();
            $table->string('startStamp', 100)->default('0');
            $table->integer('wholeError')->default(0);
            $table->integer('mfError')->default(0);
            $table->integer('feedbackScore')->default(0);
            $table->integer('delissue')->default(0);
            $table->integer('defects')->default(0);

            // Google Related Fields
            $table->string('g_id', 100)->default('0');
            $table->string('g_num', 100)->default('0');
            $table->string('g_desc', 128)->default('0');
            $table->string('g_ship', 24)->default('0');

            // Additional Status Fields
            $table->integer('oos')->default(0);
            $table->integer('mgmt')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('paidBeds')->default(0);
            $table->string('pTime', 16)->default('0');
            $table->integer('refundBox')->default(0);
            $table->integer('hold')->default(0);
            $table->integer('missed')->default(0);
            $table->integer('urgent')->default(0);
            $table->integer('retention')->default(0);
            $table->integer('paypal')->default(0);
            $table->integer('reopen')->default(0);
            $table->integer('id_supplier')->default(0);
            $table->integer('paid_supplier')->default(0);
            $table->integer('invoice_made')->default(0);
            $table->integer('id_seller')->default(0);
            $table->integer('seller_paid')->default(0);
            $table->text('paypal_payment')->nullable();
            $table->string('last_trans_id', 50)->default('0');
            $table->integer('to_collect')->default(0);
            $table->integer('is_split')->default(0);
            $table->string('voucher_code', 64)->nullable();
            $table->integer('is_shipped')->default(0);
            $table->integer('paid_courier')->default(0);
            $table->string('tnt_consignment', 48)->nullable();
            $table->string('ajfoams_tracking_number', 48)->nullable();
            $table->string('bedtatstic_tracking_number', 48)->nullable();
            $table->integer('is_collected')->default(0);
            $table->integer('escalate')->default(0);
            $table->integer('ordered')->default(0);
            $table->string('merch_ref', 50)->nullable();
            $table->integer('trust_pilot')->default(0);
            $table->integer('stolen')->default(0);
            $table->boolean('tuffnells')->default(0);
            $table->boolean('manual_order')->default(0);
            $table->integer('delivery_issue')->default(0);
            $table->unsignedInteger('parent_order_id')->nullable();
            $table->boolean('invoiceStatus')->default(0);
            $table->string('invoiced_time_stamp', 100)->nullable();
            $table->boolean('invoicePaidStatus')->default(0);
            $table->string('invoicePaid_time_stamp', 100)->nullable();

            // Order Number Parts
            $table->char('order_number_part_0', 1)->nullable()->comment('R');
            $table->char('order_number_part_1', 4)->nullable()
                ->comment('REQUEST_SOURCE_BEDS = \'B\'; REQUEST_SOURCE_EBAY = \'E\'; REQUEST_SOURCE_OTHER = \'T\'; REQUEST_SOURCE_STAFF = \'S\';');
            $table->char('order_number_part_2', 13)->nullable()->comment('uniqid()');
            $table->char('order_number_part_3', 3)->nullable()->comment('mt_rand(1, 999)');
            $table->char('order_number_part_4', 4)->nullable()
                ->comment('EXTENSION_BASE = \'P\'; EXTENSION_EXCHANGE = \'E\'; EXTENSION_SPLIT_ORDER = \'P\';');

            // Additional Fields
            $table->string('vehicle_requirement_id')->nullable();
            $table->string('territory_id')->nullable();
            $table->string('speed_zone_id')->nullable();
            $table->text('description')->nullable();
            $table->text('collection');
            $table->text('weight')->nullable();
            $table->float('volume')->nullable();
            $table->integer('error')->nullable();
            $table->string('operation_duration', 11)->nullable();
            $table->float('total_distance')->nullable();
            $table->float('total_duration')->nullable();
            $table->integer('locked')->default(1);
            $table->integer('warehouse_id')->nullable();


            // Time Related Fields
            $table->text('return_distance')->nullable();
            $table->text('return_duration')->nullable();
            $table->string('loadingTime')->nullable();
            $table->string('waitingTime')->nullable();
            $table->string('workStartTime')->nullable();
            $table->string('returnStartTime')->nullable();
            $table->string('returnWareHouseTime')->nullable();
            $table->string('arriveTime')->nullable();
            $table->string('operationEndTime')->nullable();
            $table->string('runDuration')->nullable();
            $table->string('runTotalDrivingTime')->nullable();
            $table->string('timeViolation')->nullable();
            $table->string('weightExceeded')->nullable();
            $table->string('howMuchWeightOver')->nullable();
            $table->string('volumeExceeded')->nullable();
            $table->string('howMuchVolumeOver')->nullable();

            // Taken Time Fields
            $table->string('takenloadingTime')->nullable();
            $table->string('takenstartTime')->nullable();
            $table->string('takenwaitingTime')->nullable();
            $table->string('takenworkStartTime')->nullable();
            $table->string('takenreturnStartTime')->nullable();
            $table->string('takenreturnWareHouseTime')->nullable();
            $table->string('takenarriveTime')->nullable();
            $table->string('takenoperationEndTime')->nullable();
            $table->string('takenrunDuration')->nullable();
            $table->string('takenrunTotalDrivingTime')->nullable();
            $table->string('takentimeViolation')->nullable();
            $table->string('takenweightExceeded')->nullable();
            $table->string('takenhowMuchWeightOver')->nullable();
            $table->string('takenvolumeExceeded')->nullable();
            $table->string('takenhowMuchVolumeOver')->nullable();

            $table->string('priority', 255)->default('0')->nullable();
            $table->string('sales_record', 10)->default('0')->nullable();
            $table->integer('id_delivery_route')->default(0)->nullable();
            $table->integer('id_order_type')->default(0)->nullable();
            $table->integer('id_payment_type')->default(0)->nullable();
            $table->integer('max_parent_order_id')->nullable();
            $table->string('delDate', 20)->nullable();
            $table->string('stop_sequence', 255)->nullable();

            $table->json('allowNotifications')->nullable();


            // Convert some existing boolean columns from int to proper boolean type
            $table->boolean('aftersales')->default(0)->nullable()->change();
            $table->boolean('done')->default(0)->nullable()->change();
            $table->boolean('notLoaded')->default(0)->nullable()->change();
            $table->boolean('amzShipped')->default(0)->nullable()->change();
            $table->boolean('refund')->default(0)->nullable()->change();
            $table->boolean('showVat')->default(0)->nullable()->change();
            $table->boolean('showNotes')->default(1)->nullable()->change();
            $table->boolean('proforma')->default(0)->change();

            $table->timestamp('time_stamp')->useCurrent();

            $table->string('exp', 20)->nullable();

            // Timestamps
            $table->timestamps();

            // Add indexes
            $table->index('id_company');
            $table->index('id_status');
            $table->index('ref_no');
            $table->index('orderStatus');
            $table->index('parent_order_id');
            $table->index('warehouse_id');
            $table->index('territory_id');
            $table->index('id_delivery_route');
            $table->index('id_order_type');
            $table->index('id_payment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
