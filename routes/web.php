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
// Public routes - không cần đăng nhập
Route::post('/register', [AuthController::class, 'register'])->name('register'); //rq đăng ký
Route::post('/login', [AuthController::class, 'login'])->name('login'); //rq đăng nhập
Route::post('/search-trips', [SearchController::class, 'searchTrips']); //tìm chuyến đi
Route::get('/popular-routes', [RouteController::class, 'getPopularRoutes']); //lịch trình(các tuyến đường phổ biến)
Route::post('/search-routes', [RouteController::class, 'searchRoutes']); //lịch trình(tìm các tuyến đường bằng cách search from - to)
Route::get('/trips/{tripId}', [TripController::class, 'show']);  //chi tiết một chuyến xe (phục vụ phần booking)
Route::get('/trips/{tripId}/seats', [BookingController::class, 'getAvailableSeats']); //Lấy danh sách ghế trống của chuyến xe có ID = tripId
Route::get('/routes/{routeId}/pickup-points', [BookingController::class, 'getPickupPoints']); //Lấy danh sách điểm đón của tuyến xe có ID = routeId
Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
Route::get('/invoices/{id}/download', [InvoiceController::class, 'download'])->name('invoices.download'); // Tải invoice



// Protected routes - cần đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('changePassword'); //thay đổi mk user
    // ========== QUẢN LÝ USERS (Admin/Manager) ==========
    // Danh sách users (có thêm phân trang, filter)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show'); // Lấy thông tin chi tiết 1 user theo ID
    Route::post('/users', [UserController::class, 'store'])->name('users.store'); // Tạo user mới
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update'); // Cập nhật user theo ID
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy'); // Xóa user theo ID

    // ========== QUẢN LÝ BUSES (Admin/Manager) ==========
    Route::get('/buses', [BusController::class, 'index'])->name('buses.index');
    Route::get('/buses/{id}', [BusController::class, 'show'])->name('buses.show'); // Lấy thông tin chi tiết 1 bus theo ID
    Route::post('/buses', [BusController::class, 'store'])->name('buses.store'); // Tạo bus mới
    Route::put('/buses/{id}', [BusController::class, 'update'])->name('buses.update'); // Cập nhật bus theo ID
    Route::delete('/buses/{id}', [BusController::class, 'destroy'])->name('buses.destroy'); // Xóa bus theo ID

    // ========== QUẢN LÝ ROUTE (Admin/Manager) ==========
    Route::get('/routes/{id}', [RouteController::class, 'show'])->name('routes.show'); // Lấy thông tin chi tiết 1 tuyến đường theo ID
    Route::post('/routes', [RouteController::class, 'store'])->name('routes.store'); // Tạo tuyến đường mới
    Route::put('/routes/{id}', [RouteController::class, 'update'])->name('routes.update'); // Cập nhật tuyến đường theo ID
    Route::delete('/routes/{id}', [RouteController::class, 'destroy'])->name('routes.destroy'); // Xóa tuyến đường theo ID

    // ========== QUẢN LÝ ĐIỂM ĐÓN (Admin/Manager) ==========
    Route::get('/pickup-points', [PickupPointController::class, 'index'])->name('pickup-points.index');
    Route::get('/pickup-points/{id}', [PickupPointController::class, 'show'])->name('pickup-points.show'); // Lấy thông tin chi tiết 1 điểm đón theo ID
    Route::post('/pickup-points', [PickupPointController::class, 'store'])->name('pickup-points.store'); // Tạo điểm đón mới
    Route::put('/pickup-points/{id}', [PickupPointController::class, 'update'])->name('pickup-points.update'); // Cập nhật điểm đón theo ID
    Route::delete('/pickup-points/{id}', [PickupPointController::class, 'destroy'])->name('pickup-points.destroy'); // Xóa điểm đón theo ID

    // ========== QUẢN LÝ CHUYẾN XE (Admin/Manager) ==========
    Route::get('/trips', [TripController::class, 'index'])->name('trips.index'); // Lấy danh sách chuyến xe
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

    // ========== INVOICE ROUTES ==========
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index'); // Lấy danh sách invoice
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show'); // Lấy thông tin chi tiết 1 invoice theo ID
    Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update'); // Cập nhật invoice theo ID
});
