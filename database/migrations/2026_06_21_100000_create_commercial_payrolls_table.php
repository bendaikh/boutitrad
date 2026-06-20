<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->date('payment_date');
            $table->string('pay_month', 7);
            $table->foreignId('commercial_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('amount_to_pay', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['commercial_id', 'pay_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_payrolls');
    }
};
