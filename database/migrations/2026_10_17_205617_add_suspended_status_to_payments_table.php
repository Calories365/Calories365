<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Payment::processCallback maps WayForPay's "Suspended" (a paused regular
     * payment) to its own status, which the column did not accept: the callback
     * failed on "Data truncated" and the status change was lost.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status enum('Approved','Declined','Reversed','Pending','Deleted', 'Refunded', 'Expired', 'Suspended')
            NOT NULL
            DEFAULT 'Pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status enum('Approved','Declined','Reversed','Pending','Deleted', 'Refunded', 'Expired')
            NOT NULL
            DEFAULT 'Pending'
        ");
    }
};
