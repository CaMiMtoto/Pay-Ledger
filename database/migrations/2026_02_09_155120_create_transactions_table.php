<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Business::class)->constrained();
            $table->foreignIdFor(\App\Models\Customer::class)->constrained();
            // +1 = customer owes (debit)
            // -1 = payment received (credit)
            $table->integer('direction');
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('transaction_date');
            $table->foreignIdFor(\App\Models\User::class, 'created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
