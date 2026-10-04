<?php

use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationItemController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactNoteController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\ProductSpecificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\ProductSpecificationArtifactController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProcurementOfferController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\QualityControlController;
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


    Route::get(
        '/organizations/{organization}/export-intelligence',
        [OrganizationController::class, 'exportIntelligenceData']
    )->name('organizations.export-intelligence');


    Route::resource('contacts', ContactController::class)
        ->except(['destroy'])
        ->names('contacts');

    Route::resource('assessments', AssessmentController::class)
        ->except(['destroy'])
        ->names('assessments');

    Route::resource('follow-ups', FollowUpController::class)
        ->except(['destroy'])
        ->names('follow-ups');

    Route::resource(
        'product-specifications',
        ProductSpecificationController::class
    )->except(['destroy']);

    Route::post(
        '/product-specifications/{productSpecification}/artifacts',
        [ProductSpecificationArtifactController::class, 'store']
    )->name('product-specification-artifacts.store');

    Route::get(
        '/product-specification-artifacts/{productSpecificationArtifact}/preview',
        [ProductSpecificationArtifactController::class, 'preview']
    )->name('product-specification-artifacts.preview');

    Route::get(
        '/product-specification-artifacts/{productSpecificationArtifact}/download',
        [ProductSpecificationArtifactController::class, 'download']
    )->name('product-specification-artifacts.download');

    Route::delete(
        '/product-specification-artifacts/{productSpecificationArtifact}',
        [ProductSpecificationArtifactController::class, 'destroy']
    )->name('product-specification-artifacts.destroy');

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

    Route::resource('quotations', QuotationController::class)
        ->except(['destroy']);

    Route::get(
        '/quotations/organization-options/{organization}',
        [QuotationController::class, 'organizationOptions']
    )->name('quotations.organization-options');

    Route::post(
        '/quotations/{quotation}/send-to-contacts',
        [QuotationController::class, 'sendToContacts']
    )->name('quotations.send-to-contacts');

    Route::post(
        '/quotations/{quotation}/send',
        [QuotationController::class, 'send']
    )->name('quotations.send');

    Route::post(
        '/quotations/{quotation}/cancel',
        [QuotationController::class, 'cancel']
    )->name('quotations.cancel');

    Route::get(
    '/procurements/{procurement}/attachments/{attachment}',
    [ProcurementController::class, 'showAttachment']
)->name('procurements.attachments.show');

    Route::get(
        '/procurements/{procurement}/offers/create',
        [ProcurementOfferController::class, 'create']
    )->name('procurements.offers.create');

    Route::post(
        '/procurements/{procurement}/offers',
        [ProcurementOfferController::class, 'store']
    )->name('procurements.offers.store');

    Route::get(
        '/procurement-offers/{offer}',
        [ProcurementOfferController::class, 'show']
    )->name('procurements.offers.show');

    Route::get(
        '/procurement-offers/{offer}/edit',
        [ProcurementOfferController::class, 'edit']
    )->name('procurements.offers.edit');

    Route::put(
        '/procurement-offers/{offer}',
        [ProcurementOfferController::class, 'update']
    )->name('procurements.offers.update');

    Route::post(
        '/procurement-offers/{offer}/withdraw',
        [ProcurementOfferController::class, 'withdraw']
    )->name('procurements.offers.withdraw');

    Route::post(
        '/procurement-offers/{offer}/accept',
        [ProcurementOfferController::class, 'accept']
    )->name('procurements.offers.accept');

    Route::post(
        '/procurement-offers/{offer}/reject',
        [ProcurementOfferController::class, 'reject']
    )->name('procurements.offers.reject');

Route::resource('procurements', ProcurementController::class)
        ->except(['destroy']);

    Route::get(
        '/orders/{order}/procurements/create',
        [ProcurementController::class, 'createForOrder']
    )->name('orders.procurements.create');

    Route::post(
        '/orders/{order}/procurements',
        [ProcurementController::class, 'storeForOrder']
    )->name('orders.procurements.store');

    Route::post(
        '/orders/{order}/production/correction',
        [OrderController::class, 'returnToProductionForCorrection']
    )->name('orders.production.correction');
Route::post(
    '/orders/{order}/production/correction/resubmit-quality-control',
    [OrderController::class, 'resubmitCorrectionToQualityControl']
)->name('orders.production.correction.resubmit-quality-control');

    
Route::get('deliveries', [DeliveryController::class, 'index'])
    ->name('deliveries.index');

Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])
    ->name('deliveries.show');

Route::post(
    'orders/{order}/delivery',
    [DeliveryController::class, 'store']
)->name('orders.delivery.store');

Route::post(
    'deliveries/{delivery}/confirm',
    [DeliveryController::class, 'confirm']
)->name('deliveries.confirm');

Route::post(
    'deliveries/{delivery}/activation-invitations',
    [DeliveryController::class, 'sendActivationInvitations']
)->name('deliveries.activation-invitations.store');


Route::post(
    'deliveries/{delivery}/offline-payment/claim',
    [DeliveryController::class, 'claimOfflinePayment']
)->name('deliveries.offline-payment.claim');

Route::post(
    'deliveries/{delivery}/offline-payment/confirm',
    [DeliveryController::class, 'confirmOfflinePayment']
)->name('deliveries.offline-payment.confirm');

Route::post(
    'deliveries/{delivery}/offline-payment/reject',
    [DeliveryController::class, 'rejectOfflinePayment']
)->name('deliveries.offline-payment.reject');

Route::resource('orders', OrderController::class)
        ->only(['index', 'show', 'edit', 'update']);

    Route::post(
        '/orders/{order}/approve',
        [OrderController::class, 'approve']
    )->name('orders.approve');

    Route::post(
        '/orders/{order}/resend-approval',
        [OrderController::class, 'resendApproval']
    )->name('orders.resend-approval');

    Route::post(
        '/orders/{order}/assign-coordinator',
        [OrderController::class, 'assignCoordinator']
    )->name('orders.assign-coordinator');

    Route::post(
        '/orders/{order}/production-plan',
        [OrderController::class, 'createProductionPlan']
    )->name('orders.production-plan.store');

    Route::post(
        '/orders/{order}/production-plan/confirm-complete',
        [OrderController::class, 'confirmProductionComplete']
    )->name('orders.production-plan.confirm-complete');

    Route::post(
        '/quotations/{quotation}/items',
        [QuotationItemController::class, 'store']
    )->name('quotation-items.store');

    Route::patch(
        '/quotations/{quotation}/items/{item}',
        [QuotationItemController::class, 'update']
    )->name('quotation-items.update');

    Route::delete(
        '/quotations/{quotation}/items/{item}',
        [QuotationItemController::class, 'destroy']
    )->name('quotation-items.destroy');
});

Route::get(
    '/public/orders/{token}',
    [PublicOrderController::class, 'show']
)->name('public.orders.show');

Route::get(
    '/public/deliveries/activate/{token}',
    [\App\Http\Controllers\PublicDeliveryActivationController::class, 'show']
)->name('public.deliveries.activate');

Route::post(
    '/public/deliveries/activate/{token}',
    [\App\Http\Controllers\PublicDeliveryActivationController::class, 'activate']
)->name('public.deliveries.activate.store');


Route::post(
    '/public/deliveries/pay-now/{token}',
    [\App\Http\Controllers\PublicDeliveryActivationController::class, 'payNow']
)->name('public.deliveries.pay-now');

Route::get(
    '/public/deliveries/payment/callback/{token}',
    [\App\Http\Controllers\PublicDeliveryActivationController::class, 'paymentCallback']
)->name('public.deliveries.payment.callback');

Route::get(
    '/public/quotations/{token}',
    [\App\Http\Controllers\Public\QuotationController::class, 'show']
)->name('public.quotations.show');

Route::post(
    '/public/quotations/{token}/accept',
    [\App\Http\Controllers\Public\QuotationController::class, 'accept']
)->name('public.quotations.accept');

Route::get(
    '/public/quotations/{token}/payment/callback',
    [\App\Http\Controllers\Public\QuotationController::class, 'paymentCallback']
)->name('public.quotations.payment.callback');

Route::post(
    '/public/quotations/{token}/reject',
    [\App\Http\Controllers\Public\QuotationController::class, 'reject']
)->name('public.quotations.reject');


Route::post(
    '/orders/{order}/production-plan/activities/{activity}/start',
    [OrderController::class, 'startProductionActivity']
)->name('orders.production-plan.activities.start');

Route::post(
    '/orders/{order}/production-plan/activities/{activity}/complete',
    [OrderController::class, 'completeProductionActivity']
)->name('orders.production-plan.activities.complete');

Route::post(
    '/orders/{order}/production-plan/activities/{activity}/unmark',
    [OrderController::class, 'unmarkProductionActivity']
)->name('orders.production-plan.activities.unmark');


Route::middleware('auth')->prefix('quality-control')->name('quality-control.')->group(function (): void {
    Route::get('/', [QualityControlController::class, 'index'])
        ->name('index');

    Route::post('/orders/{order}/start', [QualityControlController::class, 'start'])
        ->name('start');

    Route::get('/inspections/{inspection}', [QualityControlController::class, 'show'])
        ->name('show');

    Route::post('/inspections/{inspection}/complete', [QualityControlController::class, 'complete'])
        ->name('complete');
});
