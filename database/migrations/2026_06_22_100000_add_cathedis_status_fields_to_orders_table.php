<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cathedis_status_code')->nullable()->after('partner_tracking_ref');
            $table->timestamp('cathedis_status_synced_at')->nullable()->after('cathedis_status_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cathedis_status_code', 'cathedis_status_synced_at']);
        });
    }
};
