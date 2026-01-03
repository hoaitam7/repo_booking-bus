<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\PickupPointController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Mail;



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

Route::get('/', function () {
    return view('welcome');
});
// Thêm route test


Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('clear-compiled');

    return "He thong da xoa cache va toi uu hoa xong!";
});

// Public routes - không cần đăng nhập
//--------------------Auth------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register'])->name('register'); //rq đăng ký
Route::post('/login', [AuthController::class, 'login'])->name('login'); //rq đăng nhập
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('resetPassword'); //quên mật khẩu theo email
//--------------------Route------------------------------------------------------------------
Route::get('/popular-routes', [RouteController::class, 'getPopularRoutes']); //lịch trình (danh sách tuyến đường)
Route::post('/search-routes', [RouteController::class, 'searchRoutes']); //lịch trình (tìm kiếm)
Route::get('/routess', [RouteController::class, 'index'])->name('routes.index'); // danh sách tuyến đường
Route::get('/routess/from-city', [RouteController::class, 'fromCity'])->name('fromCity'); // lấy danh sách điểm đi
Route::get('/routess/to-city', [RouteController::class, 'toCity'])->name('toCity'); // lấy danh sách điểm đến
//--------------------Search------------------------------------------------------------------
Route::post('/search-trips', [SearchController::class, 'searchTrips']); //tìm chuyến đi
//--------------------Trip------------------------------------------------------------------
Route::get('/trips/{id}', [TripController::class, 'show']);  //chi tiết một chuyến xe (booking)
Route::get('/trips', [TripController::class, 'index'])->name('trips.index'); // Lấy danh sách chuyến xe
//--------------------Booking------------------------------------------------------------------
Route::get('/trips/{id}/seats', [BookingController::class, 'getAvailableSeats']); //Lấy danh sách ghế trống của chuyến xe có ID = tripId
Route::get('/routes/{routeId}/pickup-points', [BookingController::class, 'getPickupPoints']); //Lấy danh sách điểm đón của tuyến xe có ID = routeId
//--------------------Invoice------------------------------------------------------------------
Route::get('/invoices/{id}/download', [InvoiceController::class, 'download'])->name('invoices.download'); // Tải hóa đơn
//--------------------PAYMENT--------------------------
Route::post('/payment/payos-webhook', [PaymentController::class, 'handleWebhook']); // Route Webhook để PayOS gọi sang



// Protected routes - cần đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('changePassword'); //thay đổi mk user
    // ========== QUẢN LÝ USERS ==========
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show'); // Lấy thông tin chi tiết 1 user theo ID
    Route::post('/users', [UserController::class, 'store'])->name('users.store'); // Tạo user mới
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update'); // Cập nhật user theo ID
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy'); // Xóa user theo ID

    // ========== QUẢN LÝ BUSES ==========
    Route::get('/buses', [BusController::class, 'index'])->name('buses.index');
    Route::get('/buses/{id}', [BusController::class, 'show'])->name('buses.show'); // Lấy thông tin chi tiết 1 bus theo ID
    Route::post('/buses', [BusController::class, 'store'])->name('buses.store'); // Tạo bus mới
    Route::put('/buses/{id}', [BusController::class, 'update'])->name('buses.update'); // Cập nhật bus theo ID
    Route::delete('/buses/{id}', [BusController::class, 'destroy'])->name('buses.destroy'); // Xóa bus theo ID

    // ========== QUẢN LÝ ROUTE ==========
    Route::get('/routess/{id}', [RouteController::class, 'show'])->name('routes.show'); // Lấy thông tin chi tiết 1 tuyến đường theo ID
    Route::post('/routess', [RouteController::class, 'store'])->name('routes.store'); // Tạo tuyến đường mới
    Route::put('/routess/{id}', [RouteController::class, 'update'])->name('routes.update'); // Cập nhật tuyến đường theo ID
    Route::delete('/routess/{id}', [RouteController::class, 'destroy'])->name('routes.destroy'); // Xóa tuyến đường theo ID

    // ========== QUẢN LÝ ĐIỂM ĐÓN ==========
    Route::get('/pickup-points', [PickupPointController::class, 'index'])->name('pickup-points.index');
    Route::get('/pickup-points/{id}', [PickupPointController::class, 'show'])->name('pickup-points.show'); // Lấy thông tin chi tiết 1 điểm đón theo ID
    Route::post('/pickup-points', [PickupPointController::class, 'store'])->name('pickup-points.store'); // Tạo điểm đón mới
    Route::put('/pickup-points/{id}', [PickupPointController::class, 'update'])->name('pickup-points.update'); // Cập nhật điểm đón theo ID
    Route::delete('/pickup-points/{id}', [PickupPointController::class, 'destroy'])->name('pickup-points.destroy'); // Xóa điểm đón theo ID

    // ========== QUẢN LÝ CHUYẾN XE ==========
    Route::get('/trips/{id}', [TripController::class, 'show'])->name('trips.show'); // Lấy thông tin chi tiết 1 chuyến xe theo ID
    Route::post('/trips', [TripController::class, 'store'])->name('trips.store'); // Tạo chuyến xe mới (đơn lẻ)
    // Route::post('/trips/bulk', [TripController::class, 'bulkCreate'])->name('trips.bulk'); // Tạo nhiều chuyến xe cùng lúc
    Route::put('/trips/{id}', [TripController::class, 'update'])->name('trips.update'); // Cập nhật chuyến xe theo ID
    Route::delete('/trips/{id}', [TripController::class, 'destroy'])->name('trips.destroy'); // Xóa chuyến xe theo ID

    // ========== BOOKING ROUTES ==========
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index'); // Lấy danh sách booking
    Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show'); // Lấy thông tin chi tiết 1 booking theo ID
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store'); // Tạo booking mới
    Route::put('/bookings/{id}', [BookingController::class, 'update'])->name('bookings.update'); // Cập nhật booking theo ID
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy'); // Xóa booking theo ID
    Route::get('/myBookings/{userId}', [BookingController::class, 'myBookings'])->name('bookings.myBookings'); // danh sách vé đã đặt của user
    Route::post('/cancelPayment/{booking_code}', [BookingController::class, 'cancelPayment'])->name('bookings.cancelPayment');
    // ========== INVOICE ROUTES ==========
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index'); // Lấy danh sách invoice
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show'); // Lấy thông tin chi tiết 1 invoice theo ID
    Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update'); // Cập nhật invoice theo ID
});
