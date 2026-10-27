<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Operational Staff')->index();
            $table->boolean('active')->default(true);
        });
        Schema::table('customers', function (Blueprint $table) { $table->decimal('opening_balance',14,2)->default(0); });
        Schema::table('suppliers', function (Blueprint $table) { $table->decimal('opening_balance',14,2)->default(0); });
        Schema::table('orders', function (Blueprint $table) { $table->string('delivery_status')->default('Pending')->index(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); });
        Schema::table('purchases', function (Blueprint $table) { $table->string('status')->default('Open')->index(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); });
        Schema::table('customer_payments', function (Blueprint $table) { $table->string('number')->unique()->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->dateTime('voided_at')->nullable(); $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('void_reason')->nullable(); });
        Schema::table('supplier_payments', function (Blueprint $table) { $table->string('number')->unique()->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->dateTime('voided_at')->nullable(); $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('void_reason')->nullable(); });
        Schema::create('customer_payment_allocations', function(Blueprint $table) { $table->id(); $table->foreignId('customer_payment_id')->constrained()->restrictOnDelete(); $table->foreignId('order_id')->constrained()->restrictOnDelete(); $table->decimal('amount',14,2); $table->timestamps(); $table->unique(['customer_payment_id','order_id'], 'cpa_payment_order_unique'); $table->index('order_id'); });
        Schema::create('supplier_payment_allocations', function(Blueprint $table) { $table->id(); $table->foreignId('supplier_payment_id')->constrained()->restrictOnDelete(); $table->foreignId('purchase_id')->constrained()->restrictOnDelete(); $table->decimal('amount',14,2); $table->timestamps(); $table->unique(['supplier_payment_id','purchase_id'], 'spa_payment_purchase_unique'); $table->index('purchase_id'); });
        Schema::create('audit_logs', function(Blueprint $table) { $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('action'); $table->string('record_type'); $table->unsignedBigInteger('record_id')->nullable(); $table->json('before_values')->nullable(); $table->json('after_values')->nullable(); $table->string('ip_address',45)->nullable(); $table->timestamps(); $table->index(['record_type','record_id']); });
        Schema::create('business_settings', function(Blueprint $table) { $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->timestamps(); });
        Schema::create('expenses', function(Blueprint $table) { $table->id(); $table->string('number')->unique(); $table->dateTime('spent_at'); $table->string('category'); $table->string('description'); $table->decimal('amount',14,2); $table->string('method')->default('Cash'); $table->string('reference')->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->dateTime('voided_at')->nullable(); $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('void_reason')->nullable(); $table->timestamps(); $table->index('spent_at'); });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses'); Schema::dropIfExists('business_settings'); Schema::dropIfExists('audit_logs'); Schema::dropIfExists('supplier_payment_allocations'); Schema::dropIfExists('customer_payment_allocations');
        Schema::table('supplier_payments', function(Blueprint $table) { $table->dropConstrainedForeignId('voided_by'); $table->dropConstrainedForeignId('recorded_by'); $table->dropUnique(['number']); $table->dropColumn(['number','voided_at','void_reason']); });
        Schema::table('customer_payments', function(Blueprint $table) { $table->dropConstrainedForeignId('voided_by'); $table->dropConstrainedForeignId('recorded_by'); $table->dropUnique(['number']); $table->dropColumn(['number','voided_at','void_reason']); });
        Schema::table('purchases', function(Blueprint $table) { $table->dropConstrainedForeignId('created_by'); $table->dropColumn('status'); });
        Schema::table('orders', function(Blueprint $table) { $table->dropConstrainedForeignId('created_by'); $table->dropColumn(['delivery_status']); });
        Schema::table('suppliers', function(Blueprint $table) { $table->dropColumn('opening_balance'); }); Schema::table('customers', function(Blueprint $table) { $table->dropColumn('opening_balance'); });
        Schema::table('users', function(Blueprint $table) { $table->dropIndex(['role']); $table->dropColumn(['role','active']); });
    }
};
