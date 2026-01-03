<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
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


    /**
     * Tải hóa đơn PDF
     */
    public function download($id)
    {
        $invoice = Invoice::with([
            'booking.user',
            'booking.trip.route', // Load thêm route để lấy thông tin tuyến đường
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

        // Dữ liệu cho view PDF - SỬA THEO ĐÚNG DATA CÓ SẴN
        $data = [
            'invoice' => $invoice,
            'company' => [
                'name' => 'HỆ THỐNG ĐẶT VÉ XE TH',
                'address' => '123 Đường ABC, Quận 1, TP.HCM',
                'phone' => '(028) 1234 5678',
                'email' => 'info@busticket.com',
                'tax_code' => '0123456789',
            ],
            'issue_date' => Carbon::now()->format('d/m/Y'),
            'due_date' => Carbon::parse($invoice->created_at)->addDays(7)->format('d/m/Y'),
        ];

        // Tạo PDF từ view
        $pdf = PDF::loadView('pdf.invoice', $data)
            ->setPaper('A6', 'portrait');
        $filename = 'invoice-' . $invoice->invoice_number . '.pdf';
        return $pdf->download($filename);
    }
}
