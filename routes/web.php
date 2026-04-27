<?php

use App\Http\Controllers\{
    DashboardController,
    ComplaintController,
    MaintenanceController,
    MaintenancePolicyController,
    NoticeController,
    VisitorController,
    FlatController,
    UserController,
    SocietyController,
    TowerController,
    FileController,
    ActivityLogController,
    ProfileController,
    NotificationController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated
Route::middleware(['auth', 'verified', 'user.active'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Complaints
    Route::resource('complaints', ComplaintController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('complaints/{complaint}/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');
    Route::post('complaints/{complaint}/comment', [ComplaintController::class, 'addComment'])->name('complaints.comment');
    Route::post('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('complaints.resolve');
    Route::post('complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('complaints.status');
    Route::post('complaints/{complaint}/dispute', [ComplaintController::class, 'dispute'])->name('complaints.dispute');

    /*
    |--------------------------------------------------------------------------
    | Maintenance Policies (FIRST – avoid conflict)
    |--------------------------------------------------------------------------
    */
    Route::prefix('maintenance/policies')->name('maintenance.policies.')->group(function () {
        Route::get('/', [MaintenancePolicyController::class, 'index'])->name('index');
        Route::get('/create', [MaintenancePolicyController::class, 'create'])->name('create');
        Route::post('/', [MaintenancePolicyController::class, 'store'])->name('store');
        Route::post('/generate', [MaintenancePolicyController::class, 'manualGenerate'])->name('generate');
        Route::post('/{policy}/activate', [MaintenancePolicyController::class, 'activate'])->name('activate');
    });

    /*
    |--------------------------------------------------------------------------
    | Maintenance (FIXED ORDER)
    |--------------------------------------------------------------------------
    */

    // ✅ STATIC ROUTES FIRST
    Route::get('maintenance/create', [MaintenanceController::class, 'create'])
        ->name('maintenance.create');

    Route::post('maintenance/generate', [MaintenanceController::class, 'generate'])
        ->name('maintenance.generate');

    // Payment routes (with constraint)
    Route::get('maintenance/{maintenance}/payment', [MaintenanceController::class, 'paymentForm'])
        ->whereNumber('maintenance')
        ->name('maintenance.payment');

    Route::post('maintenance/{maintenance}/payment', [MaintenanceController::class, 'processPayment'])
        ->whereNumber('maintenance')
        ->name('maintenance.process-payment');

    // ✅ DYNAMIC ROUTES LAST
    Route::resource('maintenance', MaintenanceController::class)
        ->only(['index', 'show']);

    // Notices
    Route::resource('notices', NoticeController::class);

    // Visitors
    Route::resource('visitors', VisitorController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('visitors/{visitor}/approve', [VisitorController::class, 'approve'])->name('visitors.approve');
    Route::post('visitors/{visitor}/reject', [VisitorController::class, 'reject'])->name('visitors.reject');
    Route::post('visitors/{visitor}/exit', [VisitorController::class, 'recordExit'])->name('visitors.exit');

    // Societies (Admin)
    Route::middleware('role:super-admin,society-admin')->group(function () {
        Route::resource('societies', SocietyController::class);
    });

    // Towers (Admin)
    Route::middleware('role:super-admin,society-admin')->group(function () {
        Route::resource('towers', TowerController::class);
    });

    // Flats
    Route::resource('flats', FlatController::class);
    Route::get('flats/{flat}/assign-residents', [FlatController::class, 'assignResidents'])->name('flats.assign-residents');
    Route::post('flats/{flat}/assign-residents', [FlatController::class, 'storeResidentAssignment'])->name('flats.store-resident');
    Route::delete('flats/{flat}/residents/{user}', [FlatController::class, 'removeResident'])->name('flats.remove-resident');

    // Users
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    // Files
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

    // Activity Logs (Admin)
    Route::middleware('role:super-admin,society-admin')->group(function () {
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{module}/{entity}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    });

    Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/',                     [NotificationController::class, 'index'])->name('index');
    Route::post('{id}/read',            [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('read-all',             [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('{id}',               [NotificationController::class, 'destroy'])->name('destroy');
    Route::delete('clear-read',         [NotificationController::class, 'clearRead'])->name('clear-read');
    Route::get('unread-count',          [NotificationController::class, 'unreadCount'])->name('unread-count');
});
});

// Auth (Breeze)
require __DIR__ . '/auth.php';