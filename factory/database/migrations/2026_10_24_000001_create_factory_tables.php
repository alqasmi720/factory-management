<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->string('business_name')->nullable(); $table->string('phone')->nullable(); $table->text('address')->nullable(); $table->text('notes')->nullable(); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->string('business_name')->nullable(); $table->string('phone')->nullable(); $table->text('address')->nullable(); $table->text('notes')->nullable(); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->dateTime('ordered_at'); $table->date('expected_at')->nullable(); $table->string('status')->default('Pending'); $table->decimal('total', 14, 2)->default(0); $table->text('notes')->nullable(); $table->string('delivery_method')->nullable(); $table->dateTime('dispatched_at')->nullable(); $table->text('delivery_notes')->nullable(); $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->string('product_type'); $table->string('paper_grade')->nullable(); $table->decimal('length', 10, 2)->nullable(); $table->decimal('width', 10, 2)->nullable(); $table->decimal('height', 10, 2)->nullable(); $table->string('unit')->default('mm'); $table->unsignedInteger('quantity'); $table->decimal('unit_price', 12, 2); $table->decimal('line_total', 14, 2); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); $table->dateTime('paid_at'); $table->decimal('amount', 14, 2); $table->string('method')->default('Cash'); $table->string('reference')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('purchases', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('supplier_id')->constrained()->restrictOnDelete(); $table->dateTime('purchased_at'); $table->string('description'); $table->decimal('total', 14, 2); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('supplier_id')->constrained()->restrictOnDelete(); $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete(); $table->dateTime('paid_at'); $table->decimal('amount', 14, 2); $table->string('method')->default('Cash'); $table->string('reference')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->string('from_status')->nullable(); $table->string('to_status'); $table->dateTime('changed_at'); $table->string('changed_by')->nullable(); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories'); Schema::dropIfExists('supplier_payments'); Schema::dropIfExists('purchases'); Schema::dropIfExists('customer_payments'); Schema::dropIfExists('order_items'); Schema::dropIfExists('orders'); Schema::dropIfExists('suppliers'); Schema::dropIfExists('customers');
    }
};
