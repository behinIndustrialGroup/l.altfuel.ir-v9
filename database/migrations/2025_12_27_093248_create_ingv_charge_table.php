<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('irngv_charges', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('amount');
            $table->text('description');
            $table->string('mobile');
            $table->string('callback_url');
            $table->string('authority');
            $table->string('ref_id');
            $table->string('status');
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
            Schema::dropIfExists('irngv_charges');
    }
};
