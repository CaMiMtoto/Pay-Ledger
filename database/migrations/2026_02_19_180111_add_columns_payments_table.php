<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\User::class, 'recorded_by')->constrained('users');
            $table->timestamp('paid_at')->useCurrent();
            $table->string('payment_reference')->nullable();
            $table->foreignIdFor(Business::class, 'business_id')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(\App\Models\User::class, 'recorded_by');
            $table->dropColumn('recorded_by');
            $table->dropColumn('paid_at');
            $table->dropColumn('payment_reference');
            $table->dropConstrainedForeignIdFor(Business::class, 'business_id');
        });
    }
};
