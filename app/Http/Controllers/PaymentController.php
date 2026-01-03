<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayOS\PayOS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    private $payOS;
    public function __construct()
    {
        // Sử dụng config thay vì env trực tiếp
        $this->payOS = new PayOS(
            "3e060d4a-172b-45fb-97bf-e047f0149a19",
            "3608d2ef-6cdf-4747-a7bb-37590cdbacc4",
            "8a6f1ac5287ae84889617220d2b93c98df79e8ab7e3ff5ecf486fb964971ed3c"
        );
    }

    //PayOs gửi backend
    public function handleWebhook(Request $request)
    {
        Log::info('PayOS webhook received', $request->all());

        try {
            $payOS = new \PayOS\PayOS(
                config('services.payos.client_id'),
                config('services.payos.api_key'),
                config('services.payos.checksum_key')
            );

            // Xác thực chữ ký Payos
            $webhookData = $payOS->webhooks->verify($request->all());

            //lấy data field OrderCode từ response Payos & thực hiện tìm mã đơn hàng trong db
            $bookingCode = $webhookData->orderCode;
            $booking = Booking::where('booking_code', $bookingCode)->first();
            if (!$booking) {
                Log::warning("Không tìm thấy booking code #$bookingCode");
                return response()->json(['success' => true], 200);
            }
            DB::transaction(function () use ($booking) {
                // Cập nhật trạng thái đơn hàng
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                // tạo hóa đơn khi thanh toán thành công
                Invoice::create([
                    'booking_id'     => $booking->id,
                    'invoice_number' => 'INV-' . $booking->booking_code,
                    'total_amount'   => $booking->total_amount,
                    'status'         => 'paid',
                ]);

                //trừ ghế
                $seatCount = count(explode(',', $booking->seat_numbers)); //(số ghế)chuyển chuỗi ghế sang mảng r đếm số phần tử
                $booking->trip()->decrement('available_seats', $seatCount); //trừ ghế theo chuyến đi trong db
            });
            //gửi mail khi thanh toán thành công
            $email = $booking->user?->email;
            if ($email) {
                Mail::raw(
                    "Cảm ơn bạn đã thanh toán.\nMã đơn: {$booking->booking_code}\nSố ghế: {$booking->seat_numbers}\nTổng tiền: {$booking->total_amount} VND",
                    function ($message) use ($email) {
                        $message->to($email)
                            ->subject('Thanh toán thành công');
                    }
                );
            }
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('PayOS Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => false], 200);
        }
    }
}
