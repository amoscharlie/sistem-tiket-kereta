<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nominal', function (Blueprint $table) {
            $table->id();
            $table->string('nominal');
            $table->timestamps();
        });

        Schema::table('pesan', function(Blueprint $table){
           
            $table->foreignId('id_nominal')->nullable()->references('id')->on('nominal');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nominal');
    }
};