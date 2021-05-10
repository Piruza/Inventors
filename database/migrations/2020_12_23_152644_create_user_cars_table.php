<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_cars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('userType');
            $table->string('brand');
            $table->string('modelId');
            $table->string('carId')->nullable();
            $table->string('carName');
            $table->string('carYear')->nullable();
            $table->string('wheelType')->nullable();
            $table->string('carModelName')->nullable();
            $table->string('transmitionType')->nullable();
            $table->string('engine')->nullable();
            $table->string('region')->nullable();
            $table->string('bodyType')->nullable();
            $table->string('specSeries')->nullable();
            $table->longText('jsonResponse')->nullable();
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
        Schema::dropIfExists('user_cars');
    }
}
