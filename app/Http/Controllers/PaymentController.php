<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayOS\PayOS;
use Illuminate\Support\Facades\Log;

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

    // public function createPaymentLink(Request $request)
    // {
    //     $domain = env('APP_FRONTEND_URL', 'https://hoaitam123.xyz');

    //     // CHỈNH SỬA TẠI ĐÂY ĐỂ FIX LỖI DIGEST TẬN GỐC
    //     $orderCode = intval($request->orderCode);
    //     $amount = intval($request->amount);

    //     // 1. Description: Tuyệt đối không dùng khoảng trắng, dấu # hoặc tiếng Việt
    //     // Việc dùng chuỗi dính liền giúp Signature ổn định khi ngân hàng trả dữ liệu về
    //     $description = "THANHTOANVE" . $orderCode;

    //     $data = [
    //         "orderCode"   => $orderCode,
    //         "amount"      => $amount,
    //         "description" => $description,
    //         "returnUrl"   => $domain . "/payment-success",
    //         "cancelUrl"   => $domain . "/payment-cancel",
    //         // 2. Thêm mảng Items: PayOS sẽ dùng tổng tiền Items để đối soát Signature
    //         // giúp tăng độ chính xác khi xác thực Redirect
    //         "items" => [
    //             [
    //                 "name" => "Ve xe #" . $orderCode,
    //                 "quantity" => 1,
    //                 "price" => $amount
    //             ]
    //         ]
    //     ];

    //     try {
    //         $response = $this->payOS->createPaymentLink($data);
    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         Log::error("PayOS Create Error: " . $e->getMessage());
    //         return response()->json([
    //             'error' => 'Lỗi tạo link thanh toán: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

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
