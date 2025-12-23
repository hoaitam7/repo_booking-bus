<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayOS\PayOS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;

class PaymentController extends Controller
{
    //
    private $payOS;


    public function __construct()
    {
        // Sử dụng config thay vì env trực tiếp
        $this->payOS = new PayOS(
            config('services.payos.client_id'),
            config('services.payos.api_key'),
            config('services.payos.checksum_key')
        );
    }

    public function handleWebhook(Request $request)
    {
        Log::info('PayOS webhook received', $request->all());

        try {
            $payOS = new PayOS(
                config('services.payos.client_id'),
                config('services.payos.api_key'),
                config('services.payos.checksum_key')
            );

            // 1. Xác thực chữ ký
            $data = $payOS->verifyPaymentWebhookData($request->all());

            // 2. Lấy orderCode
            $bookingId = $data['orderCode'] ?? null;
            if (!$bookingId) {
                Log::warning('Webhook thiếu orderCode');
                return response()->json(['success' => false], 200);
            }

            $booking = Booking::find($bookingId);
            if (!$booking) {
                Log::warning("Không tìm thấy booking #$bookingId");
                return response()->json(['success' => true], 200);
            }

            // 3. Nếu đã paid thì bỏ qua (tránh webhook gửi lại)
            if ($booking->payment_status === 'paid') {
                return response()->json(['success' => true], 200);
            }

            // 4. Cập nhật trạng thái
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                if ($booking->invoice) {
                    $booking->invoice->update([
                        'status' => 'paid'
                    ]);
                }
            });

            Log::info("Booking #$bookingId thanh toán thành công");

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('PayOS Webhook Error: ' . $e->getMessage());

            // ⚠️ PHẢI TRẢ 200 để PayOS không retry vô hạn
            return response()->json(['success' => false], 200);
        }
    }
}
