<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Lấy danh sách hóa đơn
     */
    public function index(): JsonResponse
    {
        $query = Invoice::with(['booking.trip', 'booking.user'])
            ->orderBy('created_at', 'desc');

        // User thường chỉ xem hóa đơn của mình
        if (Auth::check() && Auth::user()->role !== 'admin') {
            $query->whereHas('booking', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        $invoices = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Xem chi tiết hóa đơn
     */
    public function show($id): JsonResponse
    {
        $invoice = Invoice::with([
            'booking.trip',
            'booking.user',
            'booking.pickupPoint'
        ])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn không tồn tại'
            ], 404);
        }

        // Kiểm tra quyền
        if (
            Auth::check() && Auth::user()->role !== 'admin' &&
            $invoice->booking->user_id != Auth::id()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền truy cập'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }

    /**
     * Cập nhật trạng thái hóa đơn
     */
    public function update(Request $request, $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn không tồn tại'
            ], 404);
        }

        // Chỉ admin mới được cập nhật
        if (Auth::check() && Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền cập nhật hóa đơn'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        $invoice->update([
            'status' => $request->status
        ]);

        // Đồng bộ trạng thái thanh toán với booking
        $booking = $invoice->booking;
        if ($booking) {
            $booking->update([
                'payment_status' => $request->status === 'paid' ? 'paid' : 'pending'
            ]);
        }

        $invoice->load(['booking']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hóa đơn thành công',
            'data' => $invoice
        ]);
    }
}
