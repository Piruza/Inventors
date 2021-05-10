<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandModelCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brand_model_categories', function (Blueprint $table) {
            $table->id();
            $table->string('carId');
            $table->string('modelId');
            $table->string('catalogId');
            $table->string('uid');
            $table->boolean('hasSubgroups');
            $table->boolean('hasParts');
            $table->longText('name');
            $table->string('img')->nullable();
            $table->string('description')->nullable();
            $table->string('parentId')->nullable();
            $table->tinyInteger('categoryLevel');
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
        Schema::dropIfExists('brand_model_categories');
    }
}
