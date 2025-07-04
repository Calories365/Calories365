<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status enum('Approved','Declined','Reversed','Pending','Deleted', 'Refunded')
            NOT NULL
            DEFAULT 'Pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status enum('Approved','Declined','Reversed','Pending','Deleted')
            NOT NULL
            DEFAULT 'Pending'
        ");
    }
};
