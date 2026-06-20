<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('charge_type')->nullable()->after('title');
            $table->string('treasury_mode')->default('caisse')->after('amount');
            $table->string('payment_number')->nullable()->after('treasury_mode');
            $table->string('bank')->nullable()->after('payment_number');
            $table->string('drawer_name')->nullable()->after('bank');
            $table->date('instrument_date')->nullable()->after('drawer_name');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'charge_type',
                'treasury_mode',
                'payment_number',
                'bank',
                'drawer_name',
                'instrument_date',
            ]);
        });
    }
};
