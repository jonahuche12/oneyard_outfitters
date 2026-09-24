<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactNoteController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::resource('staff', StaffController::class)
        ->except(['destroy'])
        ->names('staff')
        ->parameters([
            'staff' => 'user',
        ]);

    Route::patch('/staff/{user}/activate', [StaffController::class, 'activate'])
        ->name('staff.activate');

    Route::patch('/staff/{user}/deactivate', [StaffController::class, 'deactivate'])
        ->name('staff.deactivate');

    Route::resource('organizations', OrganizationController::class)
        ->except(['destroy']);

    Route::patch(
        '/organizations/{organization}/activate',
        [OrganizationController::class, 'activate']
    )->name('organizations.activate');

    Route::patch(
        '/organizations/{organization}/deactivate',
        [OrganizationController::class, 'deactivate']
    )->name('organizations.deactivate');


    Route::resource('contacts', ContactController::class)
        ->except(['destroy'])
        ->names('contacts');

    Route::resource('assessments', AssessmentController::class)
        ->except(['destroy'])
        ->names('assessments');

    Route::resource('follow-ups', FollowUpController::class)
        ->except(['destroy'])
        ->names('follow-ups');

    Route::delete(
        '/contacts/{contact}',
        [ContactController::class, 'destroy']
    )->name('contacts.destroy');

    Route::patch(
        '/contacts/{contact}/activate',
        [ContactController::class, 'activate']
    )->name('contacts.activate');

    Route::patch(
        '/contacts/{contact}/deactivate',
        [ContactController::class, 'deactivate']
    )->name('contacts.deactivate');

    Route::get(
        '/contacts/{contact}/notes/create',
        [ContactNoteController::class, 'create']
    )->name('contact-notes.create');

    Route::post(
        '/contacts/{contact}/notes',
        [ContactNoteController::class, 'store']
    )->name('contact-notes.store');

    Route::get(
        '/contact-notes/{contactNote}/edit',
        [ContactNoteController::class, 'edit']
    )->name('contact-notes.edit');

    Route::put(
        '/contact-notes/{contactNote}',
        [ContactNoteController::class, 'update']
    )->name('contact-notes.update');

    Route::delete(
        '/contact-notes/{contactNote}',
        [ContactNoteController::class, 'destroy']
    )->name('contact-notes.destroy');
});
