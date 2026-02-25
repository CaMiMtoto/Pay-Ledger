<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a payment', function () {
    // Arrange
    Business::factory()->create();
    $customer = Customer::factory()->create();
    $paymentService = new PaymentService();
    $amount = 100;
    $transaction_date = now()->toDateString();
    $description = 'Test payment';

    // Act
    $payment = $paymentService->save($customer, $amount, $transaction_date, $description);

    // Assert
    $this->assertInstanceOf(Payment::class, $payment);
    $this->assertDatabaseHas('payments', [
        'customer_id' => $customer->id,
        'amount' => $amount,
        'paid_at' => $transaction_date,
        'description' => $description,
    ]);
});

it('updates debts and creates payment allocations', function () {
    // Arrange
    Business::factory()->create();
    $customer = Customer::factory()->create();
    $payment = Payment::factory()->create([
        'customer_id' => $customer->id,
        'amount' => 150,
    ]);
    $debt1 = Debt::factory()->create([
        'customer_id' => $customer->id,
        'amount' => 100,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);
    $debt2 = Debt::factory()->create([
        'customer_id' => $customer->id,
        'amount' => 100,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);
    $paymentService = new PaymentService();

    // Act
    $paymentService->updateDebts($payment, $customer->id);

    // Assert
    $this->assertDatabaseHas('debts', [
        'id' => $debt1->id,
        'paid_amount' => 100,
        'status' => 'paid',
    ]);
    $this->assertDatabaseHas('debts', [
        'id' => $debt2->id,
        'paid_amount' => 50,
        'status' => 'partial',
    ]);
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $payment->id,
        'debt_id' => $debt1->id,
        'amount_applied' => 100,
    ]);
    $this->assertDatabaseHas('payment_allocations', [
        'payment_id' => $payment->id,
        'debt_id' => $debt2->id,
        'amount_applied' => 50,
    ]);
});
