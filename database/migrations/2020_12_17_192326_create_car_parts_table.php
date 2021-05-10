<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_model_part_id');
            $table->string('uid')->nullable();
            $table->string('number')->nullable();
            $table->longText('name')->nullable();
            $table->string('notice')->nullable();
            $table->longText('description')->nullable();
            $table->longText('positionNumber')->nullable();
            $table->string('url')->nullable();
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
        Schema::dropIfExists('car_parts');
    }
}
