<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\BapssController;
use App\Http\Controllers\CombatSiteController;
use App\Http\Controllers\DashboardMasterController;
use App\Http\Controllers\DashboardViewController;
use App\Http\Controllers\DataSiteUnlockController;
use App\Http\Controllers\DataPotensiAssetTowerController;
use App\Http\Controllers\DataPotensiSearchController;
use App\Http\Controllers\DataPotensiSiteController;
use App\Http\Controllers\DataPotensiSiteOwnerController;
use App\Http\Controllers\ElectricityCentralizedController;
use App\Http\Controllers\ElectricityCentralizedListrikPlnController;
use App\Http\Controllers\ElectricityDashboardController;
use App\Http\Controllers\ElectricityInbuildingController;
use App\Http\Controllers\JaknetContractController;
use App\Http\Controllers\PnlViewController;
use App\Http\Controllers\PoHqController;
use App\Http\Controllers\PoVarcostController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecurringIpasController;
use App\Http\Controllers\RecurringTagihanIpasController;
use App\Http\Controllers\EquipmentRelocationController;
use App\Http\Controllers\SewaLahanRenewalController;
use App\Http\Controllers\UploadFileController;
use App\Http\Controllers\InfrastructureUploadController;
use App\Http\Controllers\InfrastructureDashboardController;
use App\Http\Controllers\PresalesController;
use App\Http\Controllers\ModuleDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/reports/data-version', fn () => response()->json(['version' => \App\Support\ReportDataVersion::current()])->header('Cache-Control', 'private, no-store'))->name('reports.data-version');
    Route::middleware('role:admin')->prefix('operations/{dataset}')->where(['dataset' => implode('|', array_keys(\App\Support\OperationalDatasets::DEFINITIONS))])->group(function () {
        Route::get('/create', [\App\Http\Controllers\OperationalDataEntryController::class, 'create'])->name('operations.create');
        Route::post('/', [\App\Http\Controllers\OperationalDataEntryController::class, 'store'])->name('operations.store');
        Route::post('/upload', [\App\Http\Controllers\OperationalDataEntryController::class, 'upload'])->name('operations.upload');
        Route::get('/template', [\App\Http\Controllers\OperationalDataEntryController::class, 'template'])->name('operations.template');
    });
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/site-loss', [NotificationController::class, 'siteLoss'])->name('notifications.site-loss');
    Route::get('/notifications/site-loss/export', [NotificationController::class, 'exportSiteLoss'])->name('notifications.site-loss.export');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/user-approvals', fn () => redirect()->route('filament.report.pages.account-approvals'))->name('user-approvals.index');
        Route::post('/user-approvals/{user}/approve', [UserApprovalController::class, 'approve'])->name('user-approvals.approve');
        Route::post('/user-approvals/{user}/reject', [UserApprovalController::class, 'reject'])->name('user-approvals.reject');
    });

    // Dashboard Utama: Master Executive Overview dengan seluruh diagram operasional
    Route::get('/dashboard', [DashboardMasterController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardMasterController::class, 'chartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/electricity-payment-data', [DashboardMasterController::class, 'electricityPaymentData'])->name('dashboard.electricity-payment-data');
    Route::get('/dashboard/electricity-payment-detail', [DashboardMasterController::class, 'electricityPaymentDetail'])->name('dashboard.electricity-payment-detail');
    Route::get('/electricity/payment-detail', \App\Http\Controllers\ElectricityPaymentDetailController::class)->name('electricity.payment-detail');

    // Profit & Loss (P & L) Khusus: Halaman Tabel Data PnL Site & Financial Analytics
    Route::get('/pnl', [PnlViewController::class, 'index'])->name('pnl.index');
    Route::get('/pnl/upload', [PnlViewController::class, 'uploadPage'])->name('pnl.upload')->middleware('role:admin');
    Route::post('/pnl/upload', [PnlViewController::class, 'upload'])->name('pnl.upload.store')->middleware('role:admin');
    Route::get('/pnl/template', [PnlViewController::class, 'downloadTemplate'])->name('pnl.template');
    Route::get('/pnl/data', [PnlViewController::class, 'data'])->name('pnl.data');
    Route::get('/pnl/highlights', [PnlViewController::class, 'highlights'])->name('pnl.highlights');
    Route::get('/pnl/analytics', [PnlViewController::class, 'analytics'])->name('pnl.analytics');
    Route::get('/pnl/export-excel', [PnlViewController::class, 'exportExcel'])->name('pnl.export-excel');
    Route::get('/pnl/site-history/{site}', [PnlViewController::class, 'siteHistory'])->name('pnl.site-history');

    Route::get('/equipment-relocation', [EquipmentRelocationController::class, 'index'])->name('equipment-relocation.index');
    Route::get('/equipment-relocation/inventory-data', [EquipmentRelocationController::class, 'inventoryData'])->name('equipment-relocation.inventory-data');
    Route::get('/equipment-relocation/relocation-data', [EquipmentRelocationController::class, 'relocationData'])->name('equipment-relocation.relocation-data');
    Route::post('/equipment-relocation/relocation-data', [EquipmentRelocationController::class, 'saveRelocation'])->name('equipment-relocation.relocation-data.store');
    Route::delete('/equipment-relocation/relocation-data', [EquipmentRelocationController::class, 'deleteRelocation'])->name('equipment-relocation.relocation-data.destroy');
    Route::get('/equipment-relocation/export-excel', [EquipmentRelocationController::class, 'exportExcel'])->name('equipment-relocation.export-excel');
    Route::post('/equipment-relocation/import-excel', [EquipmentRelocationController::class, 'importExcel'])->name('equipment-relocation.import-excel');
    Route::redirect('/rru', '/equipment-relocation')->name('rru.index');

    // Presales adalah alur sirkulasi dan persetujuan dokumen dari sistem acuan.
    Route::get('/po-monitoring', [ModuleDashboardController::class, 'purchaseOrders'])->name('po-monitoring.dashboard');
    Route::get('/po-monitoring/details', [ModuleDashboardController::class, 'purchaseOrderDetails'])->name('po-monitoring.details');
    Route::prefix('po-monitoring/presales')->name('presales.')->group(function () {
        Route::get('/', [PresalesController::class, 'index'])->name('index');
        Route::get('/create', [PresalesController::class, 'create'])->name('create');
        Route::post('/', [PresalesController::class, 'store'])->name('store');
        Route::get('/{document}/file', [PresalesController::class, 'file'])->name('file');
        Route::get('/{document}', [PresalesController::class, 'show'])->name('show');
        Route::post('/{document}/status', [PresalesController::class, 'updateStatus'])->name('status');
    });
    Route::get('/po-monitoring/document-circulation', fn () => redirect()->route('presales.index'));
    Route::get('/po-monitoring/document-circulation/create', fn () => redirect()->route('presales.create'));
    Route::get('/po-monitoring/document-circulation/{document}', fn ($document) => redirect()->route('presales.show', $document));
    Route::post('/po-monitoring/document-circulation', [PresalesController::class, 'store']);
    Route::post('/po-monitoring/document-circulation/{document}/status', [PresalesController::class, 'updateStatus']);

    // Sumber data DataTables — WAJIB dideklarasikan sebelum resource agar
    // tidak tertangkap oleh route /po-hq/{po_hq}.
    Route::get('/po-hq/data', [PoHqController::class, 'data'])->name('po-hq.data');

    // Satu resource utuh (jangan dipecah dua: route show /po-hq/{po_hq}
    // akan membayangi /po-hq/create jika resource mutasi dideklarasikan
    // sesudahnya). Proteksi mutasi diterapkan per-method via middlewareFor().
    Route::resource('po-hq', PoHqController::class)
        ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin');

    Route::get('/po-varcost/data', [PoVarcostController::class, 'data'])->name('po-varcost.data');
    Route::get('/po-varcost/export-excel', [PoVarcostController::class, 'exportExcel'])->name('po-varcost.export-excel');
    Route::get('/po-varcost', [PoVarcostController::class, 'index'])->name('po-varcost.index');
    Route::post('/po-varcost', [PoVarcostController::class, 'store'])->name('po-varcost.store')->middleware('role:admin');
    Route::put('/po-varcost/{po_varcost}', [PoVarcostController::class, 'update'])->name('po-varcost.update')->middleware('role:admin');
    Route::delete('/po-varcost/{po_varcost}', [PoVarcostController::class, 'destroy'])->name('po-varcost.destroy')->middleware('role:admin');

    // Data Potensi
    Route::prefix('data-potensi')->name('data-potensi.')->group(function () {
        Route::get('/', [ModuleDashboardController::class, 'feasibility'])->name('dashboard');
        Route::get('site-owner', [DataPotensiSiteOwnerController::class, 'index'])->name('site-owner.index');
        Route::get('site-owner/data', [DataPotensiSiteOwnerController::class, 'data'])->name('site-owner.data');
        Route::get('site-owner/export-excel', [DataPotensiSiteOwnerController::class, 'exportExcel'])->name('site-owner.export-excel');
        Route::get('site-owner/{siteOwner}', [DataPotensiSiteOwnerController::class, 'show'])->name('site-owner.show');
        Route::get('data-site', [DataPotensiSiteController::class, 'index'])->name('data-site.index');
        Route::get('data-site/data', [DataPotensiSiteController::class, 'data'])->name('data-site.data');
        Route::get('data-site/export-excel', [DataPotensiSiteController::class, 'exportExcel'])->name('data-site.export-excel');
        Route::get('data-site/{siteId}', [DataPotensiSiteController::class, 'show'])->name('data-site.show');
        Route::get('asset-tower', [DataPotensiAssetTowerController::class, 'index'])->name('asset-tower.index');
        Route::get('asset-tower/data', [DataPotensiAssetTowerController::class, 'data'])->name('asset-tower.data');
        Route::get('asset-tower/export-excel', [DataPotensiAssetTowerController::class, 'exportExcel'])->name('asset-tower.export-excel');
        Route::get('asset-tower/{assetTower}', [DataPotensiAssetTowerController::class, 'show'])->name('asset-tower.show');
        Route::get('search-all-resource', [DataPotensiSearchController::class, 'index'])->name('search-all-resource.index');
        Route::get('search-all-resource/data', [DataPotensiSearchController::class, 'data'])->name('search-all-resource.data');
        Route::get('search-all-resource/{siteId}', [DataPotensiSearchController::class, 'show'])->name('search-all-resource.show');
    });

    // ────────────────────────────────────────────────────────────────
    // Infrastruktur Management
    // ────────────────────────────────────────────────────────────────
    Route::prefix('infrastruktur')->name('infrastruktur.')->group(function () {
        Route::get('/', [InfrastructureDashboardController::class, 'index'])->name('index');
        Route::get('/dashboard-data', [InfrastructureDashboardController::class, 'data'])->name('dashboard.data');
        Route::get('/dashboard-details', [InfrastructureDashboardController::class, 'details'])->name('dashboard.details');
        Route::post('{dataset}/manual', [\App\Http\Controllers\InfrastructureManualEntryController::class, 'store'])
            ->whereIn('dataset', array_keys(\App\Support\InfrastructureManualEntry::DATASETS))
            ->name('manual.store')->middleware('role:admin');

        // Upload dataset per modul (halaman khusus, tanpa menu baru).
        Route::get('{dataset}/upload', [InfrastructureUploadController::class, 'create'])
            ->whereIn('dataset', ['sewa-lahan', 'combat', 'recurring-ipas', 'recurring-tagihan-ipas', 'jaknet', 'site-unlock', 'bapss'])
            ->name('upload');
        Route::get('{dataset}/template', [InfrastructureUploadController::class, 'template'])
            ->whereIn('dataset', ['sewa-lahan', 'combat', 'recurring-ipas', 'recurring-tagihan-ipas', 'jaknet', 'site-unlock', 'bapss'])
            ->name('upload.template');
        Route::get('{dataset}/export-excel', [InfrastructureUploadController::class, 'export'])
            ->whereIn('dataset', ['recurring-tagihan-ipas', 'site-unlock'])
            ->name('upload.export');
        Route::post('{dataset}/upload', [InfrastructureUploadController::class, 'store'])
            ->whereIn('dataset', ['sewa-lahan', 'combat', 'recurring-ipas', 'recurring-tagihan-ipas', 'jaknet', 'site-unlock', 'bapss'])
            ->name('upload.store')->middleware('role:admin');

        // -- Combat --
        Route::get('combat/data', [CombatSiteController::class, 'data'])->name('combat.data');
        Route::get('combat/export-excel', [CombatSiteController::class, 'exportExcel'])->name('combat.export-excel');
        Route::resource('combat', CombatSiteController::class)
            ->only(['index', 'show', 'edit', 'update', 'destroy'])
            ->parameters(['combat' => 'combat'])
            ->middlewareFor(['edit', 'update', 'destroy'], 'role:admin');

        // -- Sewa Lahan (Renewal) --
        Route::get('sewa-lahan/data', [SewaLahanRenewalController::class, 'data'])->name('sewa-lahan.data');
        Route::get('sewa-lahan/export-excel', [SewaLahanRenewalController::class, 'exportExcel'])->name('sewa-lahan.export-excel');
        Route::get('sewa-lahan/{sewaLahan}/cetak-sip', [SewaLahanRenewalController::class, 'cetakSip'])->name('sewa-lahan.cetak-sip');
        Route::resource('sewa-lahan', SewaLahanRenewalController::class)
            ->only(['index', 'edit', 'update', 'destroy'])
            ->parameters(['sewa-lahan' => 'sewaLahan'])
            ->middlewareFor(['edit', 'update', 'destroy'], 'role:admin');

        // -- Recurring (ANT & Ipas) --
        Route::get('recurring-ipas', [RecurringIpasController::class, 'index'])->name('recurring-ipas.index');
        Route::get('recurring-ipas/data', [RecurringIpasController::class, 'data'])->name('recurring-ipas.data');
        Route::get('recurring-ipas/export-excel', [RecurringIpasController::class, 'exportExcel'])->name('recurring-ipas.export-excel');
        Route::get('recurring-ipas/export-csv', [RecurringIpasController::class, 'exportCsv'])->name('recurring-ipas.export-csv');

        // -- Recurring (Tagihan Ipas) --
        Route::get('recurring-tagihan-ipas', [RecurringTagihanIpasController::class, 'index'])->name('recurring-tagihan-ipas.index');
        Route::get('recurring-tagihan-ipas/analytics', [RecurringTagihanIpasController::class, 'analytics'])->name('recurring-tagihan-ipas.analytics');
        Route::get('recurring-tagihan-ipas/details', [RecurringTagihanIpasController::class, 'details'])->name('recurring-tagihan-ipas.details');
        Route::get('recurring-tagihan-ipas/data', [RecurringTagihanIpasController::class, 'data'])->name('recurring-tagihan-ipas.data');
        Route::get('recurring-tagihan-ipas/{site}', [RecurringTagihanIpasController::class, 'show'])->whereNumber('site')->name('recurring-tagihan-ipas.show');

        // -- Sewa Lahan (Jaknet & Dapot) --
        Route::get('jaknet', [JaknetContractController::class, 'index'])->name('jaknet.index');
        Route::get('jaknet/data', [JaknetContractController::class, 'data'])->name('jaknet.data');
        Route::get('jaknet/export-excel', [JaknetContractController::class, 'exportExcel'])->name('jaknet.export-excel');

        // -- Data Site Unlock --
        Route::get('site-unlock', [DataSiteUnlockController::class, 'index'])->name('site-unlock.index');
        Route::get('site-unlock/data', [DataSiteUnlockController::class, 'data'])->name('site-unlock.data');

        // -- BAPSS --
        Route::get('bapss/data', [BapssController::class, 'data'])->name('bapss.data');
        Route::get('bapss/export-excel', [BapssController::class, 'exportExcel'])->name('bapss.export-excel');
        Route::get('bapss/export-csv', [BapssController::class, 'exportCsv'])->name('bapss.export-csv');
        Route::get('bapss/{bapss}/file/{type}', [BapssController::class, 'file'])
            ->whereIn('type', ['bapss', 'dismantle'])
            ->name('bapss.file');
        Route::get('bapss/{bapss}', [BapssController::class, 'show'])->name('bapss.show');
        Route::resource('bapss', BapssController::class)
            ->only(['index', 'edit', 'update', 'destroy'])
            ->parameters(['bapss' => 'bapss'])
            ->middlewareFor(['edit', 'update', 'destroy'], 'role:admin');

        // -- Upload File PDF --
        Route::get('upload-file/data', [UploadFileController::class, 'data'])->name('upload-file.data');
        Route::get('upload-file/{uploadFile}/file', [UploadFileController::class, 'file'])->name('upload-file.file');
        Route::resource('upload-file', UploadFileController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['upload-file' => 'uploadFile'])
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'role:admin');
    });

    // URL konsep warisan diarahkan ke modul Laravel aktif sehingga tidak ada
    // lagi halaman prototipe, data dummy, atau localStorage yang dibutuhkan
    // untuk mengakses fitur yang sama.
    Route::redirect('/infrastructure-management', '/infrastruktur')
        ->name('concept.infrastructure-management');
    Route::redirect('/sewa-lahan', '/infrastruktur/sewa-lahan')
        ->name('concept.sewa-lahan');
    Route::redirect('/site-telkomsel', '/infrastruktur/sewa-lahan?filter_field=ownership&filter_value=Telkomsel')
        ->name('concept.site-telkomsel');
    Route::redirect('/site-tp', '/infrastruktur/sewa-lahan?filter_field=ownership&filter_value=TP')
        ->name('concept.site-tp');
    Route::redirect('/combat', '/infrastruktur/combat')
        ->name('concept.combat');
    Route::redirect('/simawar', '/dashboard')
        ->name('concept.simawar');

    // ────────────────────────────────────────────────────────────────
    // Electricity Management
    // ────────────────────────────────────────────────────────────────
    Route::prefix('electricity')->name('electricity.')->group(function () {

        Route::get('/', [ElectricityDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard-data', [ElectricityDashboardController::class, 'data'])->name('dashboard.data');

        // -- Centralized: Listrik PLN --
        Route::prefix('centralized')->name('centralized.')->group(function () {
            Route::get('listrik-pln', [ElectricityCentralizedListrikPlnController::class, 'index'])->name('listrik-pln.index');
            Route::get('listrik-pln/data', [ElectricityCentralizedListrikPlnController::class, 'data'])->name('listrik-pln.data');
            Route::get('listrik-pln/export-excel', [ElectricityCentralizedListrikPlnController::class, 'exportExcel'])->name('listrik-pln.export-excel');
            Route::get('listrik-pln/{listrikPln}/edit', [ElectricityCentralizedListrikPlnController::class, 'edit'])->name('listrik-pln.edit');
            Route::put('listrik-pln/{listrikPln}', [ElectricityCentralizedListrikPlnController::class, 'update'])->name('listrik-pln.update')
                ->middleware('role:admin');

            // Status Pembayaran (Sub-tabel Listrik PLN)
            Route::get('listrik-pln/{listrikPln}/status-pembayaran', [\App\Http\Controllers\ElectricityStatusPembayaranController::class, 'index'])
                ->name('listrik-pln.status-pembayaran.index');
            Route::get('listrik-pln/{listrikPln}/status-pembayaran/data', [\App\Http\Controllers\ElectricityStatusPembayaranController::class, 'data'])
                ->name('listrik-pln.status-pembayaran.data');
            Route::get('listrik-pln/{listrikPln}/status-pembayaran/export-excel', [\App\Http\Controllers\ElectricityStatusPembayaranController::class, 'exportExcel'])
                ->name('listrik-pln.status-pembayaran.export-excel');
            Route::post('listrik-pln/{listrikPln}/status-pembayaran', [\App\Http\Controllers\ElectricityStatusPembayaranController::class, 'store'])
                ->name('listrik-pln.status-pembayaran.store')
                ->middleware('role:admin');
            Route::put('status-pembayaran/{statusPembayaran}', [\App\Http\Controllers\ElectricityStatusPembayaranController::class, 'update'])
                ->name('status-pembayaran.update')
                ->middleware('role:admin');
            Route::delete('status-pembayaran/{statusPembayaran}', [\App\Http\Controllers\ElectricityStatusPembayaranController::class, 'destroy'])
                ->name('status-pembayaran.destroy')
                ->middleware('role:admin');

            // Grafik Tagihan
            Route::get('listrik-pln/{listrikPln}/grafik', [ElectricityCentralizedListrikPlnController::class, 'grafikTagihan'])
                ->name('listrik-pln.grafik');
            Route::get('listrik-pln/{listrikPln}/grafik-data', [ElectricityCentralizedListrikPlnController::class, 'grafikData'])
                ->name('listrik-pln.grafik-data');

            // Bongkar Rampung (Boram)
            Route::get('listrik-pln/{listrikPln}/bongkar-rampung/create', [ElectricityCentralizedListrikPlnController::class, 'showBongkarRampungCreate'])
                ->name('listrik-pln.bongkar-rampung.create');
            Route::post('listrik-pln/{listrikPln}/bongkar-rampung', [ElectricityCentralizedListrikPlnController::class, 'storeBongkarRampung'])
                ->name('listrik-pln.bongkar-rampung.store')
                ->middleware('role:admin');

            // Payment (Gabungan Payment Done & Pending)
            Route::get('payment', [\App\Http\Controllers\ElectricityCentralizedPaymentController::class, 'index'])->name('payment.index');
            Route::get('payment/data', [\App\Http\Controllers\ElectricityCentralizedPaymentController::class, 'data'])->name('payment.data');
            Route::get('payment/export-excel', [\App\Http\Controllers\ElectricityCentralizedPaymentController::class, 'exportExcel'])->name('payment.export-excel');
            Route::get('payment/upload', [\App\Http\Controllers\ElectricityCentralizedPaymentController::class, 'showUpload'])->name('payment.upload-page')->middleware('role:admin');
            Route::get('payment/template', [\App\Http\Controllers\ElectricityCentralizedPaymentController::class, 'template'])->name('payment.template')->middleware('role:admin');
            Route::post('payment/upload', [\App\Http\Controllers\ElectricityCentralizedPaymentController::class, 'upload'])->name('payment.upload')->middleware('role:admin');

            // Anomali Tagihan PLN
            Route::get('anomali', [\App\Http\Controllers\ElectricityCentralizedAnomaliController::class, 'index'])->name('anomali.index');
            Route::get('anomali/data', [\App\Http\Controllers\ElectricityCentralizedAnomaliController::class, 'data'])->name('anomali.data');
            Route::get('anomali/export-excel', [\App\Http\Controllers\ElectricityCentralizedAnomaliController::class, 'exportExcel'])->name('anomali.export-excel');
            Route::get('anomali/upload', [\App\Http\Controllers\ElectricityCentralizedAnomaliController::class, 'showUpload'])->name('anomali.upload-page')->middleware('role:admin');
            Route::get('anomali/template', [\App\Http\Controllers\ElectricityCentralizedAnomaliController::class, 'template'])->name('anomali.template')->middleware('role:admin');
            Route::post('anomali/upload', [\App\Http\Controllers\ElectricityCentralizedAnomaliController::class, 'upload'])->name('anomali.upload')->middleware('role:admin');

            // Bongkar Rampung (Tabel Mandiri)
            Route::get('bongkar-rampung-mandiri', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'index'])->name('bongkar-rampung-mandiri.index');
            Route::get('bongkar-rampung-mandiri/data', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'data'])->name('bongkar-rampung-mandiri.data');
            Route::get('bongkar-rampung-mandiri/export-excel', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'exportExcel'])->name('bongkar-rampung-mandiri.export-excel');
            Route::get('bongkar-rampung-mandiri/upload', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'showUpload'])->name('bongkar-rampung-mandiri.upload-page')->middleware('role:admin');
            Route::get('bongkar-rampung-mandiri/template', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'template'])->name('bongkar-rampung-mandiri.template')->middleware('role:admin');
            Route::post('bongkar-rampung-mandiri/upload', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'upload'])->name('bongkar-rampung-mandiri.upload')->middleware('role:admin');
            Route::post('bongkar-rampung-mandiri', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'store'])->name('bongkar-rampung-mandiri.store')
                ->middleware('role:admin');
            Route::put('bongkar-rampung-mandiri/{bongkarRampungMandiri}', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'update'])->name('bongkar-rampung-mandiri.update')
                ->middleware('role:admin');
            Route::delete('bongkar-rampung-mandiri/{bongkarRampungMandiri}', [\App\Http\Controllers\ElectricityCentralizedBoramController::class, 'destroy'])->name('bongkar-rampung-mandiri.destroy')
                ->middleware('role:admin');

            // Listrik All (Matriks 12 Bulan)
            Route::get('listrik-all', [\App\Http\Controllers\ElectricityCentralizedListrikAllController::class, 'index'])->name('listrik-all.index');
            Route::get('listrik-all/data', [\App\Http\Controllers\ElectricityCentralizedListrikAllController::class, 'data'])->name('listrik-all.data');
            Route::get('listrik-all/export-excel', [\App\Http\Controllers\ElectricityCentralizedListrikAllController::class, 'exportExcel'])->name('listrik-all.export-excel');
            Route::get('listrik-all/upload', [\App\Http\Controllers\ElectricityCentralizedListrikAllController::class, 'showUpload'])->name('listrik-all.upload-page')->middleware('role:admin');
            Route::get('listrik-all/template', [\App\Http\Controllers\ElectricityCentralizedListrikAllController::class, 'template'])->name('listrik-all.template')->middleware('role:admin');
            Route::post('listrik-all/upload', [\App\Http\Controllers\ElectricityCentralizedListrikAllController::class, 'upload'])->name('listrik-all.upload')->middleware('role:admin');

            // Upload Data Flagging
            Route::get('upload-flagging', [ElectricityCentralizedController::class, 'showUploadFlagging'])->name('upload-flagging')->middleware('role:admin');
            Route::post('upload-flagging', [ElectricityCentralizedController::class, 'uploadFlagging'])->name('upload-flagging.store')->middleware('role:admin');
            Route::get('template-flagging', [ElectricityCentralizedController::class, 'downloadTemplateFlagging'])->name('template-flagging')->middleware('role:admin');
        });

        // -- Inbuilding --
        Route::prefix('inbuilding')->name('inbuilding.')->group(function () {
            // Listrik Inbuilding
            Route::get('listrik-inbuilding', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'index'])->name('listrik-inbuilding.index');
            Route::get('listrik-inbuilding/data', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'data'])->name('listrik-inbuilding.data');
            Route::get('listrik-inbuilding/export-excel', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'exportExcel'])->name('listrik-inbuilding.export-excel');
            Route::get('listrik-inbuilding/{listrikInbuilding}', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'show'])->name('listrik-inbuilding.show');
            Route::get('listrik-inbuilding/{listrikInbuilding}/chart-data', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'chartData'])->name('listrik-inbuilding.chart-data');
            Route::post('listrik-inbuilding', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'store'])->name('listrik-inbuilding.store')
                ->middleware('role:admin');
            Route::put('listrik-inbuilding/{listrikInbuilding}', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'update'])->name('listrik-inbuilding.update')
                ->middleware('role:admin');
            Route::delete('listrik-inbuilding/{listrikInbuilding}', [\App\Http\Controllers\ElectricityInbuildingListrikController::class, 'destroy'])->name('listrik-inbuilding.destroy')
                ->middleware('role:admin');

            // Input Data Tagihan IBC (Manual Form)
            Route::get('input-tagihan-ibc', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'index'])->name('input-tagihan-ibc.index');
            Route::get('input-tagihan-ibc/create', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'create'])->name('input-tagihan-ibc.create');
            Route::get('input-tagihan-ibc/{inputUploadTagihanIbc}/edit', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'edit'])->name('input-tagihan-ibc.edit')
                ->middleware('role:admin');
            Route::get('input-tagihan-ibc/data', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'data'])->name('input-tagihan-ibc.data');
            Route::post('input-tagihan-ibc', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'store'])->name('input-tagihan-ibc.store')
                ->middleware('role:admin');
            Route::put('input-tagihan-ibc/{inputUploadTagihanIbc}', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'update'])->name('input-tagihan-ibc.update')
                ->middleware('role:admin');
            Route::get('input-tagihan-ibc/{inputUploadTagihanIbc}', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'show'])->name('input-tagihan-ibc.show');
            Route::delete('input-tagihan-ibc/{inputUploadTagihanIbc}', [\App\Http\Controllers\ElectricityInbuildingInputTagihanController::class, 'destroy'])->name('input-tagihan-ibc.destroy')
                ->middleware('role:admin');

            // Payment IBC (Gabungan Done + Pending)
            Route::get('payment', [\App\Http\Controllers\ElectricityInbuildingPaymentController::class, 'index'])->name('payment.index');
            Route::get('payment/data', [\App\Http\Controllers\ElectricityInbuildingPaymentController::class, 'data'])->name('payment.data');
            Route::get('payment/export-excel', [\App\Http\Controllers\ElectricityInbuildingPaymentController::class, 'exportExcel'])->name('payment.export-excel');

            // Anomali Tagihan Inbuilding
            Route::get('anomali', [\App\Http\Controllers\ElectricityInbuildingAnomaliController::class, 'index'])->name('anomali.index');
            Route::get('anomali/data', [\App\Http\Controllers\ElectricityInbuildingAnomaliController::class, 'data'])->name('anomali.data');
            Route::get('anomali/export-excel', [\App\Http\Controllers\ElectricityInbuildingAnomaliController::class, 'exportExcel'])->name('anomali.export-excel');

            // Inbuilding All (12 Bulan Matriks)
            Route::get('inbuilding-all', [\App\Http\Controllers\ElectricityInbuildingAllController::class, 'index'])->name('inbuilding-all.index');
            Route::get('inbuilding-all/data', [\App\Http\Controllers\ElectricityInbuildingAllController::class, 'data'])->name('inbuilding-all.data');
            Route::get('inbuilding-all/export-excel', [\App\Http\Controllers\ElectricityInbuildingAllController::class, 'exportExcel'])->name('inbuilding-all.export-excel');

            // Upload Data Tagihan IBC
            Route::get('upload-tagihan-ibc', [ElectricityInbuildingController::class, 'showUploadTagihanIbc'])->name('upload-tagihan-ibc')->middleware('role:admin');
            Route::post('upload-tagihan-ibc', [ElectricityInbuildingController::class, 'uploadTagihanIbc'])->name('upload-tagihan-ibc.store')->middleware('role:admin');
            Route::get('template-tagihan-ibc', [ElectricityInbuildingController::class, 'downloadTemplateTagihanIbc'])->name('template-tagihan-ibc')->middleware('role:admin');
        });
    });
});
