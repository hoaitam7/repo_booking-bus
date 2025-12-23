<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayOS\PayOS;

class PaymentController extends Controller
{
    //
    private $payOS;

    // app/Http/Controllers/PaymentController.php

    public function __construct()
    {
        // Sử dụng config thay vì env trực tiếp
        $this->payOS = new PayOS(
            config('services.payos.client_id'),
            config('services.payos.api_key'),
            config('services.payos.checksum_key')
        );
    }

    public function createPaymentLink(Request $request)
    {
        $domain = env('APP_URL'); // Lấy domain từ .env (vd: http://localhost:8000)

        $data = [
            "orderCode" => intval(substr(strval(microtime(true) * 10000), -6)),
            "amount" => 2000,
            "description" => "Thanh toán đơn hàng",
            "items" => [
                [
                    'name' => 'Mì tôm Hảo Hảo ly',
                    'price' => 2000,
                    'quantity' => 1
                ]
            ],
            "returnUrl" => $domain . "/payment-success",
            "cancelUrl" => $domain . "/payment-cancel"
        ];

        try {
            $response = $this->payOS->createPaymentLink($data);
            // Trả JSON về cho React xử lý
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payOS = new \PayOS\PayOS(
            config('services.payos.client_id'),
            config('services.payos.api_key'),
            config('services.payos.checksum_key')
        );

        try {
            // 1. Xác thực dữ liệu
            $webhookData = $payOS->verifyPaymentWebhookData($request->all());

            // 2. Xử lý khi nhấn nút "Lưu" trên PayOS (Dữ liệu test)
            if ($webhookData['description'] == 'Ma don hang' || $webhookData['orderCode'] == 123) {
                return response()->json(['success' => true]);
            }

            // 3. Xử lý đơn hàng thật
            $bookingId = $webhookData['orderCode'];
            $booking = \App\Models\Booking::find($bookingId);

            if ($booking && $booking->status === 'pending') {
                \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
                    $booking->update([
                        'status' => 'confirmed',
                        'payment_status' => 'paid'
                    ]);

                    if ($booking->invoice) {
                        $booking->invoice->update(['status' => 'paid']);
                    }
                });
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Quan trọng: Trả về 200 để PayOS không báo lỗi "Webhook không phản hồi"
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 200);
        }
    }
}
