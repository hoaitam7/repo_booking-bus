<?php
// app/Http/Controllers/BookingController.php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\Route;
use App\Models\PickupPoint;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class BookingController extends Controller
{
    /**
     * Lấy danh sách booking (admin xem tất cả, user xem của mình)
     */
    public function index(): JsonResponse
    {
        $query = Booking::with(['trip', 'pickupPoint', 'user', 'invoice'])
            ->orderBy('created_at', 'desc');

        // User thường chỉ xem booking của mình
        if (Auth::check() && Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::id());
        }

        $bookings = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }


    /**
     * Xem chi tiết booking
     */
    public function show($id): JsonResponse
    {
        $booking = Booking::with([
            'trip.route',
            'trip.bus',
            'pickupPoint',
            'user',
            'invoice'
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        // Kiểm tra quyền: user chỉ xem được booking của mình
        if (Auth::check() && Auth::user()->role !== 'admin' && $booking->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * Đặt vé
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'pickup_point_id' => 'required|exists:pickup_points,id',
            'seat_numbers' => 'required|string|max:255',
            'passenger_name' => 'required|string|max:255',
            'passenger_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cash,banking,momo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra chuyến xe có tồn tại và còn chỗ không
        $trip = Trip::find($request->trip_id);
        if (!$trip || $trip->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không khả dụng'
            ], 400);
        }

        // Kiểm tra số ghế còn trống
        $requestedSeats = explode(',', $request->seat_numbers); //['A1', 'A2','A4'];  // A3, A4, A5
        $seatCount = count($requestedSeats); //3

        if ($seatCount > $trip->available_seats) { //43 > 40 
            return response()->json([
                'success' => false,
                'message' => 'Số ghế bạn chọn vượt quá số ghế còn trống'
            ], 400);
        }

        // Lấy danh sách các ghế đã được đặt cho chuyến xe này
        $alreadyBookedSeats = Booking::where('trip_id', $request->trip_id)
            ->whereIn('status', ['confirmed', 'pending']) // Chỉ xem các booking đã xác nhận hoặc đang chờ
            ->pluck('seat_numbers')
            ->flatMap(function ($seats) {
                return explode(',', $seats);
            })
            ->toArray();
        // Tìm các ghế trùng lặp
        $duplicateSeats = array_intersect($requestedSeats, $alreadyBookedSeats);

        if (!empty($duplicateSeats)) {
            // Nếu có ghế trùng lặp, trả về lỗi
            return response()->json([
                'success' => false,
                'message' => 'Các ghế sau đã được đặt: ' . implode(', ', $duplicateSeats),
                'booked_seats' => $duplicateSeats
            ], 409); // Sử dụng mã 409 Conflict hoặc 400 Bad Request
        }

        // Kiểm tra điểm đón có thuộc tuyến của chuyến xe không
        $pickupPoint = PickupPoint::find($request->pickup_point_id);
        if (!$pickupPoint || $pickupPoint->route_id !== $trip->route_id) {
            return response()->json([
                'success' => false,
                'message' => 'Điểm đón không hợp lệ cho chuyến xe này'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Tính tổng tiền
            $totalAmount = $trip->ticket_price * $seatCount;

            // Tạo booking
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'trip_id' => $request->trip_id,
                'pickup_point_id' => $request->pickup_point_id,
                'seat_numbers' => $request->seat_numbers,
                'passenger_name' => $request->passenger_name,
                'passenger_phone' => $request->passenger_phone,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'status' => 'confirmed',
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'paid',
            ]);

            // Cập nhật số ghế trống
            $trip->available_seats -= $seatCount;
            $trip->save();

            // Tạo invoice
            $invoiceData = [
                'booking_id' => $booking->id,
                'invoice_number' => 'INV' . date('YmdHis') . rand(100, 999),
                'total_amount' => $totalAmount,
                'status' => $request->payment_method === 'cash' ? 'pending' : 'paid',
            ];

            $invoice = Invoice::create($invoiceData);

            DB::commit();

            // Load relationships
            $booking->load(['trip.route', 'trip.bus', 'pickupPoint', 'invoice']);

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'data' => [
                    'booking' => $booking,
                    'invoice' => $invoice
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt vé: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật booking
     */
    public function update(Request $request, $id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        // Kiểm tra quyền: user chỉ cập nhật booking của mình
        if (Auth::check() && Auth::user()->role !== 'admin' && $booking->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền cập nhật booking này'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'sometimes|in:pending,paid,refunded,failed',
            'passenger_name' => 'sometimes|string|max:255',
            'passenger_phone' => 'sometimes|string|max:20',
            'payment_method' => 'sometimes|in:cash,banking,momo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Xử lý hủy booking: hoàn lại ghế
            if (
                $request->has('status') && $request->status === 'cancelled' &&
                $booking->status !== 'cancelled'
            ) {

                // Hoàn lại số ghế cho chuyến xe
                $seatCount = count(explode(',', $booking->seat_numbers));
                $trip = Trip::find($booking->trip_id);
                if ($trip) {
                    $trip->available_seats += $seatCount;
                    $trip->save();
                }
            }

            // Cập nhật booking
            $booking->update($request->only([
                'status',
                'payment_status',
                'passenger_name',
                'passenger_phone',
                'payment_method'
            ]));

            // Cập nhật invoice nếu có
            if ($booking->invoice && $request->has('payment_status')) {
                $booking->invoice->update([
                    'status' => $request->payment_status
                ]);
            }

            DB::commit();

            $booking->load(['trip', 'pickupPoint', 'invoice']);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật booking thành công',
                'data' => $booking
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Update booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật'
            ], 500);
        }
    }

    /**
     * Xóa booking (chỉ admin)
     */
    public function destroy($id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        // Chỉ admin mới được xóa
        if (Auth::check() && Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền xóa booking'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Hoàn lại ghế nếu booking chưa hủy
            if ($booking->status !== 'cancelled') {
                $seatCount = count(explode(',', $booking->seat_numbers));
                $trip = Trip::find($booking->trip_id);
                if ($trip) {
                    $trip->available_seats += $seatCount;
                    $trip->save();
                }
            }

            // Xóa invoice trước (nếu có)
            if ($booking->invoice) {
                $booking->invoice->delete();
            }

            // Xóa booking
            $booking->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa booking thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Delete booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa booking'
            ], 500);
        }
    }

    /**
     * Lấy danh sách booking của user hiện tại
     */
    public function myBookings(): JsonResponse
    {
        $bookings = Booking::with(['trip', 'pickupPoint', 'invoice'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Lấy danh sách ghế trống của chuyến xe
     */
    public function getAvailableSeats($tripId): JsonResponse
    {
        $trip = Trip::with(['bus'])->find($tripId);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không tồn tại'
            ], 404);
        }

        // Tạo danh sách ghế linh hoạt theo số ghế thực tế
        $allSeats = [];
        $totalSeats = $trip->bus->total_seats;

        $rows = range('A', 'Z');
        $seatsPerRow = 4;

        $seatCount = 0;
        foreach ($rows as $row) {
            for ($seatNum = 1; $seatNum <= $seatsPerRow; $seatNum++) {
                if ($seatCount >= $totalSeats) break 2;

                $allSeats[] = $row . $seatNum;
                $seatCount++;
            }
        }

        // Lấy ghế đã đặt
        $bookedSeats = Booking::where('trip_id', $tripId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('seat_numbers')
            ->flatMap(function ($seats) {
                return explode(',', $seats);
            })
            ->toArray();

        $availableSeats = array_diff($allSeats, $bookedSeats);

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $tripId,
                'bus_name' => $trip->bus->bus_name,
                'bus_type' => $trip->bus->bus_type,
                'total_seats' => $trip->bus->total_seats,
                'available_seats' => array_values($availableSeats),
                'booked_seats' => array_values($bookedSeats),
                'all_seats' => $allSeats,
                'seat_layout' => $this->generateFlexibleSeatLayout($allSeats, $bookedSeats, $trip->bus->bus_type)
            ]
        ]);
    }

    /**
     * Tạo layout ghế linh hoạt theo loại xe
     */
    private function generateFlexibleSeatLayout($allSeats, $bookedSeats, $busType)
    {
        $layout = [];
        $currentRow = '';
        $rowSeats = [];

        foreach ($allSeats as $seat) {
            $row = $seat[0];
            $number = (int) substr($seat, 1);

            if ($row !== $currentRow) {
                if (!empty($rowSeats)) {
                    $layout[] = [
                        'row' => $currentRow,
                        'seats' => $rowSeats
                    ];
                }
                $currentRow = $row;
                $rowSeats = [];
            }

            // Xác định loại ghế theo vị trí
            $seatType = $this->getSeatType($number, $busType);

            $rowSeats[] = [
                'seat_number' => $seat,
                'is_available' => !in_array($seat, $bookedSeats),
                'seat_type' => $seatType,
                'floor' => $number <= 2 ? 'lower' : 'upper'
            ];
        }

        if (!empty($rowSeats)) {
            $layout[] = [
                'row' => $currentRow,
                'seats' => $rowSeats
            ];
        }

        return $layout;
    }

    /**
     * Xác định loại ghế
     */
    private function getSeatType($seatNumber, $busType)
    {
        if ($busType === 'sleeper') {
            return $seatNumber <= 2 ? 'lower_berth' : 'upper_berth';
        } else {
            return 'standard';
        }
    }

    /**
     * Lấy danh sách điểm đón của tuyến xe
     */
    public function getPickupPoints($routeId): JsonResponse
    {
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Tuyến đường không tồn tại'
            ], 404);
        }

        $pickupPoints = PickupPoint::where('route_id', $routeId)
            ->orderBy('name')
            ->get(['id', 'route_id', 'name', 'address']);

        if ($pickupPoints->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy điểm đón cho tuyến đường này'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'route_info' => [
                    'id' => $route->id,
                    'from_city' => $route->from_city,
                    'to_city' => $route->to_city
                ],
                'pickup_points' => $pickupPoints
            ]
        ]);
    }
}
