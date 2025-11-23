<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wastes', function (Blueprint $table) {
            $table->enum('status', ['pending','accepted','rejected','re-scheduled','completed'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('wastes', function (Blueprint $table) {
            $table->enum('status', ['pending','accepted','rejected','re-scheduled'])
                ->default('pending')
                ->change();
        });
    }
};
