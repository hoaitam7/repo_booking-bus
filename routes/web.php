<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\BookingController;
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


// Protected routes - cần đăng nhập
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('changePassword');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile'); //lấy thong tin user
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('updateProfile'); //cập nhật ttt user

    //Booking
    Route::post('/bookings', [BookingController::class, 'store']); // đặt vé 
    // Route::get('/bookings', [BookingController::class, 'index']);
    // Route::get('/bookings/{id}', [BookingController::class, 'show']);
    // Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
});
