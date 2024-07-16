<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inputs', function (Blueprint $table) {
            $table->id();
            $table->text('system');
            $table->text('title');
            $table->text('description');
            $table->json('additional_inputs')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inputs');
    }
};
