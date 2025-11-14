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
        Schema::create('wastes', function (Blueprint $table) {
            $table->id();
            $table->enum("waste_type",['recyclable', 'non-recyclable']);
            $table->foreignId('user_id')->constrained();
            $table->decimal('latitude',10,7)->nullable();
            $table->decimal('longitude',10,7)->nullable();
            $table->string('address')->nullable();
            $table->integer("weight");
            $table->date("date");
            $table->enum("shift",['9AM-12PM','12PM-3PM','3PM-6PM']);
            $table->enum('status',['pending','accepted','rejected','re-scheduled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wastes');
    }
};
