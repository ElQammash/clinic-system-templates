<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All money columns are integer minor units (piastres): 150.50 EGP = 15050.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('category', 50)->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('visit_types', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('name_en')->constrained()->nullOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->string('discount_type', 10)->default('fixed');  // fixed | percent
            $table->unsignedBigInteger('discount_value')->default(0); // fixed: minor units, percent: hundredths of a percent (1250 = 12.5%)
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('balance')->default(0);
            $table->string('status', 20)->default('unpaid');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'issued_at']);
            $table->index(['status', 'issued_at']);
            $table->index(['doctor_id', 'issued_at']);
            $table->index('issued_at');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('discount')->default(0); // line discount, minor units
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable(); // InstaPay / card / wallet reference
            $table->dateTime('paid_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index(['method', 'paid_at']);
            $table->index('patient_id');
        });

        // Expenses (optional module)
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('spent_at');
            $table->string('description');
            $table->string('payment_method', 20)->default('cash');
            $table->string('receipt_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('spent_at');
        });

        // Inventory (optional module)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 30)->default('قطعة');
            $table->integer('quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // in | out | adjust
            $table->integer('quantity'); // signed change
            $table->integer('balance_after');
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::table('visit_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
        Schema::dropIfExists('services');
    }
};
