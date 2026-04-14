<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Entities
    Route::inertia('entities', 'entities/Index')->name('entities.index');
    Route::inertia('entities/{id}', 'entities/Show')->name('entities.show');

    // People
    Route::inertia('people', 'people/Index')->name('people.index');
    Route::inertia('people/{id}', 'people/Show')->name('people.show');

    // Deals
    Route::inertia('deals', 'deals/Index')->name('deals.index');
    Route::inertia('deals/{id}', 'deals/Show')->name('deals.show');

    // Calendar
    Route::inertia('calendar', 'calendar/Index')->name('calendar.index');

    // Automation
    Route::inertia('automation/email-templates', 'automation/EmailTemplates')->name('automation.email-templates');
    Route::inertia('automation/rules', 'automation/Rules')->name('automation.rules');

    // Lead Forms
    Route::inertia('lead-forms', 'lead-forms/Index')->name('lead-forms.index');

    // Product Statistics
    Route::inertia('products/statistics', 'products/Statistics')->name('products.statistics');

    // Products CRUD
    Route::inertia('products', 'products/Index')->name('products.index');

    // AI
    Route::inertia('chat', 'ai/Chat')->name('ai.chat');
    Route::inertia('ai/suggestions', 'ai/Suggestions')->name('ai.suggestions');
});

require __DIR__.'/settings.php';
