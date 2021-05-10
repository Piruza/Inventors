<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandModelPartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_model_parts', function (Blueprint $table) {
            $table->id();
            $table->string('carId');
            $table->string('modelId');
            $table->string('catalogId');
            $table->string('groupId');
            $table->string('img')->nullable();
            $table->string('imgDescription')->nullable();
            $table->longText('partGroups')->nullable();
            $table->longText('positions')->nullable();
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
        Schema::dropIfExists('brand_model_parts');
    }
}
