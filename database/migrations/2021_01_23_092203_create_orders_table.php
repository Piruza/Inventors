<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('carId');
            $table->string('partId');
            $table->integer('partStatusId');
            $table->bigInteger('orderStatusId')->default(1);
            $table->bigInteger('searchingTypeId')->default(1);
            $table->tinyInteger('hasOfferAccepted')->default(0);
            $table->bigInteger('acceptedOfferId')->default(null);
            $table->timestamp('acceptedOfferTime')->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
