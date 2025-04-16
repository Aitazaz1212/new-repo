<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archiveOrders', function (Blueprint $table) {
            $table->id();
            $table->integer('cid')->nullable();
            $table->string('orderStatus', 100)->nullable();
            $table->string('startDate', 50)->nullable();
            $table->string('startTime', 50)->nullable();
            $table->string('endDate', 50)->nullable();
            $table->string('endTime', 50)->nullable();
            $table->string('staffName', 50)->nullable();
            $table->integer('staffid')->nullable();
            $table->double('paid')->default(0)->nullable();
            $table->double('total')->default(0)->nullable();
            $table->string('lastMaintainer', 100)->default('')->nullable();
            $table->integer('lastMaintainID')->nullable();
            $table->string('delRoute', 200)->nullable();
            $table->string('dispatchType', 100)->default('Delivery')->nullable();
            $table->string('collector', 200)->nullable();
            $table->string('colDelDate', 100)->nullable();
            $table->string('carrier', 200)->nullable();
            $table->string('orderType', 100)->nullable();
            $table->integer('direct')->nullable();
            $table->string('paymentType', 200)->nullable();
            $table->integer('delOrder')->nullable();
            $table->string('authNo', 100)->nullable();
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
            $table->string('ebayID', 400)->nullable();
            $table->double('vatAmount')->nullable();
            $table->integer('zeroVat')->default(0)->nullable();
            $table->string('ebayStatus', 100)->nullable();
            $table->string('deliverBy', 100)->default('0');
            $table->integer('tx')->default(0);
            $table->string('delTime', 20)->nullable();
            $table->string('delStat', 20)->nullable();
            $table->string('agent', 400)->nullable();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archiveOrders');
    }
}; 