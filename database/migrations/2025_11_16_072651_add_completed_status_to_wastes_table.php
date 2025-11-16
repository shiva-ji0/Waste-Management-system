<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        DB::statement("ALTER TABLE wastes MODIFY COLUMN status
            ENUM('pending', 'accepted', 're-scheduled', 'collected', 'completed', 'cancelled')
            NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wastes MODIFY COLUMN status
            ENUM('pending', 'accepted', 're-scheduled', 'collected', 'cancelled')
            NOT NULL DEFAULT 'pending'");
    }
};
