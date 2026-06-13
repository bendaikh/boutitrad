<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('prospection')->nullable()->after('city');
            $table->string('payment_mode')->nullable()->after('prospection');
            $table->foreignId('commercial_id')->nullable()->after('payment_mode')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commercial_id');
            $table->dropColumn(['prospection', 'payment_mode']);
        });
    }
};
