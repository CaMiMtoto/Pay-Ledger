<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

require __DIR__ . '/settings.php';

Route::group(['middleware' => 'auth', 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::livewire('/dashboard', 'dashboard.overview')->middleware(['auth', 'verified'])->name('dashboard');
    Route::livewire('/customers', 'customers.customer-index')->name('customers.index');
    Route::livewire('/businesses', 'business.list')->name('businesses.list');
});
