<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupPointController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// =========================================================================
// HOME & UTILITY ROUTES
// =========================================================================

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('clear-compiled');

    return "He thong da xoa cache va toi uu hoa xong!";
});


// =========================================================================
// PUBLIC ROUTES (Không cần đăng nhập)
// =========================================================================


Route::post('/chat', [ChatController::class, 'chat']);
// -------------------------------------------------------------------------
// Authentication
// -------------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register'])
    ->name('register'); // Đăng ký tài khoản mới

Route::post('/login', [AuthController::class, 'login'])
    ->name('login'); // Đăng nhập và nhận token

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('resetPassword'); // Quên mật khẩu - gửi email mật khẩu mới

// -------------------------------------------------------------------------
// Routes (Tuyến đường)
// -------------------------------------------------------------------------
Route::get('/popular-routes', [RouteController::class, 'getPopularRoutes'])
    ->name('routes.popular'); // Lấy danh sách tuyến đường phổ biến

Route::post('/search-routes', [RouteController::class, 'searchRoutes'])
    ->name('routes.search'); // Tìm kiếm tuyến đường theo keyword

Route::get('/routess', [RouteController::class, 'index'])
    ->name('routes.index'); // Lấy danh sách tất cả tuyến đường

Route::get('/routess/from-city', [RouteController::class, 'fromCity'])
    ->name('routes.fromCity'); // Lấy danh sách điểm đi (không trùng lặp)

Route::get('/routess/to-city', [RouteController::class, 'toCity'])
    ->name('routes.toCity'); // Lấy danh sách điểm đến (không trùng lặp)

// -------------------------------------------------------------------------
// Search (Tìm kiếm)
// -------------------------------------------------------------------------
Route::post('/search-trips', [SearchController::class, 'searchTrips'])
    ->name('trips.search'); // Tìm kiếm chuyến xe (một chiều/khứ hồi)

// -------------------------------------------------------------------------
// Trips (Chuyến xe)
// -------------------------------------------------------------------------
Route::get('/trips', [TripController::class, 'index'])
    ->name('trips.index'); // Lấy danh sách chuyến xe (có filter)

Route::get('/trips/{id}', [TripController::class, 'show'])
    ->name('trips.show'); // Chi tiết một chuyến xe

// -------------------------------------------------------------------------
// Booking (Đặt vé)
// -------------------------------------------------------------------------
Route::get('/trips/{id}/seats', [BookingController::class, 'getAvailableSeats'])
    ->name('trips.seats'); // Lấy danh sách ghế trống của chuyến xe

Route::get('/routes/{routeId}/pickup-points', [BookingController::class, 'getPickupPoints'])
    ->name('routes.pickupPoints'); // Lấy danh sách điểm đón của tuyến xe

// -------------------------------------------------------------------------
// Invoice (Hóa đơn)
// -------------------------------------------------------------------------
Route::get('/invoices/{id}/download', [InvoiceController::class, 'download'])
    ->name('invoices.download'); // Tải hóa đơn PDF

// -------------------------------------------------------------------------
// Payment (Thanh toán)
// -------------------------------------------------------------------------
Route::post('/payment/payos-webhook', [PaymentController::class, 'handleWebhook'])
    ->name('payment.webhook'); // Webhook để PayOS gọi sang khi thanh toán


// =========================================================================
// PROTECTED ROUTES (Yêu cầu đăng nhập - auth:sanctum)
// =========================================================================

Route::middleware('auth:sanctum')->group(function () {

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------
    Route::post('/change-password', [AuthController::class, 'changePassword'])
        ->name('auth.changePassword'); // Đổi mật khẩu

    // -------------------------------------------------------------------------
    // Users Management (Quản lý người dùng)
    // -------------------------------------------------------------------------
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index'); // Danh sách users

    Route::get('/users/{id}', [UserController::class, 'show'])
        ->name('users.show'); // Chi tiết user theo ID

    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store'); // Tạo user mới

    Route::put('/users/{id}', [UserController::class, 'update'])
        ->name('users.update'); // Cập nhật user theo ID

    Route::delete('/users/{id}', [UserController::class, 'destroy'])
        ->name('users.destroy'); // Xóa user theo ID

    // -------------------------------------------------------------------------
    // Buses Management (Quản lý xe bus)
    // -------------------------------------------------------------------------
    Route::get('/buses', [BusController::class, 'index'])
        ->name('buses.index'); // Danh sách buses

    Route::get('/buses/{id}', [BusController::class, 'show'])
        ->name('buses.show'); // Chi tiết bus theo ID

    Route::post('/buses', [BusController::class, 'store'])
        ->name('buses.store'); // Tạo bus mới

    Route::put('/buses/{id}', [BusController::class, 'update'])
        ->name('buses.update'); // Cập nhật bus theo ID

    Route::delete('/buses/{id}', [BusController::class, 'destroy'])
        ->name('buses.destroy'); // Xóa bus theo ID

    // -------------------------------------------------------------------------
    // Routes Management (Quản lý tuyến đường)
    // -------------------------------------------------------------------------
    Route::get('/routess/{id}', [RouteController::class, 'show'])
        ->name('routes.show'); // Chi tiết tuyến đường theo ID

    Route::post('/routess', [RouteController::class, 'store'])
        ->name('routes.store'); // Tạo tuyến đường mới

    Route::put('/routess/{id}', [RouteController::class, 'update'])
        ->name('routes.update'); // Cập nhật tuyến đường theo ID

    Route::delete('/routess/{id}', [RouteController::class, 'destroy'])
        ->name('routes.destroy'); // Xóa tuyến đường theo ID

    // -------------------------------------------------------------------------
    // Pickup Points Management (Quản lý điểm đón)
    // -------------------------------------------------------------------------
    Route::get('/pickup-points', [PickupPointController::class, 'index'])
        ->name('pickup-points.index'); // Danh sách điểm đón

    Route::get('/pickup-points/{id}', [PickupPointController::class, 'show'])
        ->name('pickup-points.show'); // Chi tiết điểm đón theo ID

    Route::post('/pickup-points', [PickupPointController::class, 'store'])
        ->name('pickup-points.store'); // Tạo điểm đón mới

    Route::put('/pickup-points/{id}', [PickupPointController::class, 'update'])
        ->name('pickup-points.update'); // Cập nhật điểm đón theo ID

    Route::delete('/pickup-points/{id}', [PickupPointController::class, 'destroy'])
        ->name('pickup-points.destroy'); // Xóa điểm đón theo ID

    // -------------------------------------------------------------------------
    // Trips Management (Quản lý chuyến xe)
    // -------------------------------------------------------------------------
    Route::post('/trips', [TripController::class, 'store'])
        ->name('trips.store'); // Tạo chuyến xe mới (đơn lẻ)

    Route::put('/trips/{id}', [TripController::class, 'update'])
        ->name('trips.update'); // Cập nhật chuyến xe theo ID

    Route::delete('/trips/{id}', [TripController::class, 'destroy'])
        ->name('trips.destroy'); // Xóa chuyến xe theo ID

    // Route::post('/trips/bulk', [TripController::class, 'bulkCreate'])
    //     ->name('trips.bulk'); // Tạo nhiều chuyến xe cùng lúc (đã comment)

    // -------------------------------------------------------------------------
    // Bookings Management (Quản lý đặt vé)
    // -------------------------------------------------------------------------
    Route::get('/bookings', [BookingController::class, 'index'])
        ->name('bookings.index'); // Danh sách bookings

    Route::get('/bookings/{id}', [BookingController::class, 'show'])
        ->name('bookings.show'); // Chi tiết booking theo ID

    Route::post('/bookings', [BookingController::class, 'store'])
        ->name('bookings.store'); // Tạo booking mới (đặt vé)

    Route::put('/bookings/{id}', [BookingController::class, 'update'])
        ->name('bookings.update'); // Cập nhật booking theo ID

    Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])
        ->name('bookings.destroy'); // Xóa booking theo ID

    Route::get('/myBookings/{userId}', [BookingController::class, 'myBookings'])
        ->name('bookings.myBookings'); // Danh sách vé đã đặt của user

    Route::post('/cancelPayment/{booking_code}', [BookingController::class, 'cancelPayment'])
        ->name('bookings.cancelPayment'); // Hủy thanh toán theo booking_code

    // -------------------------------------------------------------------------
    // Invoices Management (Quản lý hóa đơn)
    // -------------------------------------------------------------------------
    Route::get('/invoices', [InvoiceController::class, 'index'])
        ->name('invoices.index'); // Danh sách invoices

    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])
        ->name('invoices.show'); // Chi tiết invoice theo ID

    Route::put('/invoices/{id}', [InvoiceController::class, 'update'])
        ->name('invoices.update'); // Cập nhật invoice theo ID

});
