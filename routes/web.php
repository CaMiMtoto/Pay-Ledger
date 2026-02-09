<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Route::livewire('/customers', 'customers.customer-index')->name('customers.index')->middleware(['auth', 'verified']);
require __DIR__ . '/settings.php';
