<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status enum('Approved','Declined','Reversed','Pending','Deleted')
            NOT NULL
            DEFAULT 'Pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status enum('Approved','Declined','Reversed','Pending')
            NOT NULL
            DEFAULT 'Pending'
        ");
    }
};
