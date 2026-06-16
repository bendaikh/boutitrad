<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('api_url')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_partner_id')->nullable()->after('livreur_id')->constrained()->nullOnDelete();
            $table->string('partner_tracking_ref')->nullable()->after('delivery_partner_id');
            $table->timestamp('submitted_to_admin_at')->nullable()->after('cancelled_at');
            $table->timestamp('sent_to_partner_at')->nullable()->after('submitted_to_admin_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_partner_id');
            $table->dropColumn(['partner_tracking_ref', 'submitted_to_admin_at', 'sent_to_partner_at']);
        });

        Schema::dropIfExists('delivery_partners');
    }
};
