<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelled_orders', function (Blueprint $table) {
            $table->integer('id')->default(0)->primary();
            $table->integer('id_company')->default(0)->nullable();
            $table->integer('id_status')->default(1)->nullable()->comment('This should be \'id_order_status\'; the values are form the \'order_status\' table');
            $table->integer('cid')->nullable();
            $table->string('delRoute', 32)->nullable();
            $table->string('endDate', 32)->nullable();
            $table->string('endTime', 32)->nullable();
            $table->string('lastMaintainer', 48)->default('')->nullable();
            $table->integer('lastMaintainID')->nullable();
            $table->string('orderStatus', 24)->nullable();
            $table->double('paid')->default(0)->nullable();
            $table->string('startDate', 24)->nullable();
            $table->string('startTime', 24)->nullable();
            $table->string('staffName', 48)->nullable();
            $table->integer('staffid')->nullable();
            $table->double('total')->default(0)->nullable();
            $table->string('dispatchType', 32)->default('Delivery')->nullable();
            $table->string('collector', 32)->nullable();
            $table->string('colDelDate', 24)->nullable();
            $table->string('carrier', 32)->nullable();
            $table->string('orderType', 32)->nullable();
            $table->integer('direct')->nullable();
            $table->string('paymentType', 32)->nullable();
            $table->integer('delOrder')->nullable();
            $table->string('authNo', 32)->nullable();
            $table->string('cardNo', 4)->nullable();
            $table->string('exp', 20)->nullable();
            $table->string('companyName', 200)->nullable();
            $table->string('ouref', 400)->nullable();
            $table->timestamp('time_stamp')->useCurrent();
            $table->integer('proforma')->default(0);
            $table->integer('showVat')->default(0)->nullable();
            $table->integer('showNotes')->default(1)->nullable();
            $table->string('serType', 100)->default('sale')->nullable();
            $table->string('icType', 100)->default('invoice')->nullable();
            $table->string('iNo', 100)->nullable();
            $table->string('ebayID', 64)->nullable();
            $table->double('vatAmount')->nullable();
            $table->integer('zeroVat')->default(0)->nullable();
            $table->string('ebayStatus', 100)->nullable();
            $table->string('deliverBy', 100)->default('0');
            $table->integer('tx')->default(0);
            $table->string('delTime', 20)->nullable();
            $table->string('delStat', 20)->nullable();
            $table->text('agent')->nullable();
            $table->integer('agentPaid')->default(0)->nullable();
            $table->double('agentOwed')->default(0)->nullable();
            $table->double('rate')->default(0)->nullable();
            $table->integer('txt')->default(0)->nullable();
            $table->string('code', 200)->nullable();
            $table->string('parcelid', 20)->nullable();
            $table->string('neighbour', 400)->default('0')->nullable();
            $table->integer('aftersales')->default(0)->nullable();
            $table->string('safePlace', 200)->default('0')->nullable();
            $table->integer('done')->default(0)->nullable();
            $table->string('ebay', 100)->default('0')->nullable();
            $table->integer('notLoaded')->default(0)->nullable();
            $table->integer('p')->default(0)->nullable();
            $table->integer('wes')->default(0)->nullable();
            $table->string('cStatus', 100)->default('none')->nullable();
            $table->integer('colOrder')->default(0)->nullable();
            $table->string('colTime', 10)->nullable();
            $table->string('colRoute', 100)->nullable();
            $table->string('extra', 10)->nullable();
            $table->string('beds', 255)->default('0')->nullable();
            $table->string('delDate', 20)->nullable();
            $table->integer('colID')->default(0)->nullable();
            $table->integer('delID')->default(0)->nullable();
            $table->string('lat', 100)->nullable();
            $table->string('lng', 100)->nullable();
            $table->string('amazonID', 100)->nullable();
            $table->integer('amzShipped')->default(0)->nullable();
            $table->integer('refund')->default(0)->nullable();
            $table->string('postLoc', 50)->default('0')->nullable();
            $table->integer('post')->default(0)->nullable();
            $table->string('sender', 200)->default('0')->nullable();
            $table->string('stamp', 100)->nullable();
            $table->string('startStamp', 100)->default('0')->nullable();
            $table->integer('wholeError')->default(0)->nullable();
            $table->integer('mfError')->default(0)->nullable();
            $table->integer('feedbackScore')->default(0)->nullable();
            $table->integer('delissue')->default(0)->nullable();
            $table->integer('defects')->default(0)->nullable();
            $table->string('g_id', 100)->default('0')->nullable();
            $table->string('g_num', 100)->default('0')->nullable();
            $table->string('g_desc', 128)->default('0')->nullable();
            $table->string('g_ship', 24)->default('0')->nullable();
            $table->integer('oos')->default(0)->nullable();
            $table->integer('mgmt')->default(0)->nullable();
            $table->integer('processed')->default(0)->nullable();
            $table->integer('paidBeds')->default(0)->nullable();
            $table->string('pTime', 16)->default('0')->nullable();
            $table->integer('refundBox')->default(0)->nullable();
            $table->integer('id_delivery_route')->default(0)->nullable();
            $table->integer('id_order_type')->default(0)->nullable();
            $table->integer('id_payment_type')->default(0)->nullable();
            $table->integer('hold')->default(0)->nullable();
            $table->integer('missed')->default(0)->nullable();
            $table->integer('urgent')->default(0)->nullable();
            $table->integer('retention')->default(0)->nullable();
            $table->integer('paypal')->default(0)->nullable();
            $table->integer('reopen')->default(0)->nullable();
            $table->integer('id_supplier')->default(0)->nullable();
            $table->integer('paid_supplier')->default(0)->nullable();
            $table->integer('invoice_made')->default(0)->nullable();
            $table->integer('id_seller')->default(0)->nullable();
            $table->integer('seller_paid')->default(0)->nullable();
            $table->decimal('discount_amount', 9, 2)->default(0.00)->nullable();
            $table->decimal('discount_rate', 6, 2)->default(0.00)->nullable()->comment('expressed as a percentage');
            $table->decimal('vat_paid', 6, 2)->default(0.00)->nullable()->comment('expressed as a percentage');
            $table->text('paypal_payment')->nullable();
            $table->string('last_trans_id', 50)->default('0')->nullable();
            $table->integer('to_collect')->default(0)->nullable();
            $table->string('deleted_by', 50)->nullable();
            $table->timestamp('delete_time')->useCurrent();
            $table->integer('is_split')->default(0)->nullable();
            $table->integer('paid_courier')->default(0)->nullable();
            $table->integer('is_collected')->default(0)->nullable();
            $table->integer('escalate')->default(0)->nullable();
            $table->integer('ordered')->default(0)->nullable();
            $table->string('merch_ref', 50)->nullable();
            $table->integer('trust_pilot')->default(0)->nullable();
            $table->integer('stolen')->default(0)->nullable();
            $table->string('sales_record', 10)->default('0')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelled_orders');
    }
}; 