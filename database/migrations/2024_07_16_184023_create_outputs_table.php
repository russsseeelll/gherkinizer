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
        Schema::create('outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('input_id')->constrained()->onDelete('cascade');
            $table->json('questions');
            $table->json('answers');
            $table->text('result');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('outputs');
    }
};
