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

    public function handleWebhook(Request $request)
    {
        $data = $this->payOS->verifyPaymentWebhookData($request->all());

        $booking = Booking::find($data['orderCode']);

        if ($booking && $data['status'] === 'PAID') {
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid'
                ]);

                $booking->invoice?->update(['status' => 'paid']);
            });
        }

        return response()->json(['success' => true], 200);
    }
}
