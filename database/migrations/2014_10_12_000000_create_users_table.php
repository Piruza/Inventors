<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::enableForeignKeyConstraints();
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fullName')->nullable();
            $table->string('email')->nullable();
            $table->string('personalNumber')->nullable();
            $table->tinyInteger('isPhysical');
            $table->string('legalName')->nullable();
            $table->string('legalContactFullName')->nullable();
            $table->string('legalPersonalNumber')->nullable();
            $table->tinyInteger('clientLegalTypeId')->nullable();
            $table->unsignedBigInteger('typeId');
            $table->string('phone')->unique();
            $table->text('doc_front_side')->nullable();
            $table->text('doc_rear_side')->nullable();
            $table->string('password');
            $table->tinyInteger('social_auth')->default(0);
            $table->tinyInteger('isApproved')->default(0);
            $table->tinyInteger('isDeleted')->default(0);
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
        Schema::dropIfExists('users');
    }
}
