<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\BrandingLogoController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PatientAttachmentController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\StartVisitController;
use App\Livewire\Appointments;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Billing;
use App\Livewire\Dashboard;
use App\Livewire\Expenses;
use App\Livewire\Inventory;
use App\Livewire\Lab;
use App\Livewire\Medicines;
use App\Livewire\Notifications\Reminders;
use App\Livewire\Patients;
use App\Livewire\Profile;
use App\Livewire\Queue\WaitingRoom;
use App\Livewire\Reports;
use App\Livewire\Settings\ActivityLog;
use App\Livewire\Settings\BackupsPage;
use App\Livewire\Settings\ClinicSettings;
use App\Livewire\Settings\CustomFields;
use App\Livewire\Settings\Roles;
use App\Livewire\Settings\Schedule;
use App\Livewire\Settings\Services;
use App\Livewire\Settings\Users;
use App\Livewire\Visits;
use Illuminate\Support\Facades\Route;

Route::get('/branding/logo', BrandingLogoController::class)->name('branding.logo');
Route::post('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::livewire('/', Dashboard::class)->name('dashboard');
    Route::livewire('/profile', Profile::class)->name('profile');

    Route::livewire('/patients', Patients\Index::class)->name('patients.index');
    Route::livewire('/patients/{patient}', Patients\Show::class)->withTrashed()->name('patients.show');
    Route::get('/attachments/{attachment}', PatientAttachmentController::class)->name('attachments.show');

    Route::livewire('/appointments', Appointments\Calendar::class)->name('appointments.index');
    Route::livewire('/queue', WaitingRoom::class)->name('queue');

    Route::livewire('/visits', Visits\Index::class)->name('visits.index');
    Route::post('/visits/start', StartVisitController::class)->name('visits.start');
    Route::livewire('/visits/{visit}', Visits\VisitEditor::class)->withTrashed()->name('visits.show');
    Route::livewire('/medicines', Medicines\Library::class)->name('medicines.index');
    Route::livewire('/lab', Lab\Index::class)->middleware('module:lab')->name('lab.index');

    Route::livewire('/invoices', Billing\Invoices::class)->name('invoices.index');
    Route::livewire('/invoices/create', Billing\InvoiceEditor::class)->name('invoices.create');
    Route::livewire('/invoices/{invoice}', Billing\InvoiceShow::class)->withTrashed()->name('invoices.show');
    Route::livewire('/invoices/{invoice}/edit', Billing\InvoiceEditor::class)->name('invoices.edit');
    Route::livewire('/expenses', Expenses\Index::class)->middleware('module:expenses')->name('expenses.index');
    Route::livewire('/inventory', Inventory\Index::class)->middleware('module:inventory')->name('inventory.index');

    Route::livewire('/reports', Reports\Index::class)->name('reports.index');
    Route::get('/reports/export/{sheet}.xlsx', [ReportExportController::class, 'excel'])->name('reports.excel');
    Route::get('/reports/export.pdf', [ReportExportController::class, 'pdf'])->name('reports.pdf');
    Route::livewire('/reminders', Reminders::class)->name('reminders');
    Route::livewire('/activity-log', ActivityLog::class)->middleware('can:activity_log.view')->name('activity-log');

    Route::prefix('print')->name('print.')->group(function () {
        Route::get('/invoices/{invoice}', [PrintController::class, 'invoice'])->withTrashed()->name('invoice');
        Route::get('/receipts/{payment}', [PrintController::class, 'receipt'])->name('receipt');
        Route::get('/prescriptions/{prescription}', [PrintController::class, 'prescription'])->name('prescription');
        Route::get('/lab-requests/{labRequest}', [PrintController::class, 'labRequest'])->middleware('module:lab')->name('lab-request');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::livewire('/custom-fields', CustomFields::class)->middleware('can:custom_fields.manage')->name('custom-fields');
        Route::livewire('/schedule', Schedule::class)->middleware('can:schedule.manage')->name('schedule');
        Route::livewire('/services', Services::class)->middleware('can:services.manage')->name('services');
        Route::livewire('/backups', BackupsPage::class)->middleware('can:backups.manage')->name('backups');
        Route::get('/backups/{name}', BackupDownloadController::class)->middleware('can:backups.manage')->name('backups.download');
        Route::livewire('/', ClinicSettings::class)->middleware('can:settings.manage')->name('clinic');
        Route::livewire('/users', Users::class)->middleware('can:users.manage')->name('users');
        Route::livewire('/roles', Roles::class)->middleware('can:roles.manage')->name('roles');
    });
});
