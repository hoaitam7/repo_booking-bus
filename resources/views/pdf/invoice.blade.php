<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Hóa đơn {{ $invoice->invoice_number }}</title>
    <style>
        /* Thêm font cho tiếng Việt */
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company {
            font-weight: bold;
            font-size: 18px;
        }

        .invoice-title {
            font-size: 20px;
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            margin-top: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .label {
            font-weight: bold;
        }

        .total {
            font-weight: bold;
            color: #d63031;
            font-size: 16px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 6px 0;
        }
    </style>
</head>

<body>
    <!-- Header công ty -->
    <div class="header">
        <div class="company">{{ $company['name'] }}</div>
        <div>Địa chỉ: {{ $company['address'] }}</div>
        <div>Điện thoại: {{ $company['phone'] }} | Email: {{ $company['email'] }}</div>
    </div>

    <!-- Tiêu đề hóa đơn -->
    <div class="invoice-title">HÓA ĐƠN THANH TOÁN</div>

    <!-- Mã hóa đơn -->
    <div style="text-align: center; margin-bottom: 20px;">
        Mã hóa đơn: <strong>{{ $invoice->invoice_number }}</strong> |
        Ngày: {{ date('d/m/Y', strtotime($invoice->created_at)) }}
    </div>

    <!-- Thông tin khách hàng -->
    <div class="section-title">THÔNG TIN KHÁCH HÀNG</div>
    <table>
        <tr>
            <td class="label" width="30%">Họ tên:</td>
            <td>{{ $invoice->booking->passenger_name }}</td>
        </tr>
        <tr>
            <td class="label">Số điện thoại:</td>
            <td>{{ $invoice->booking->passenger_phone }}</td>
        </tr>
        <tr>
            <td class="label">Email:</td>
            <td>{{ $invoice->booking->user->email }}</td>
        </tr>
    </table>

    <!-- Thông tin chuyến đi -->
    <div class="section-title">THÔNG TIN CHUYẾN ĐI</div>
    <table>
        <tr>
            <td class="label" width="30%">Mã đặt chỗ:</td>
            <td>{{ $invoice->booking->booking_code }}</td>
        </tr>
        <tr>
            <td class="label">Số ghế:</td>
            <td>{{ $invoice->booking->seat_numbers }}</td>
        </tr>
        <tr>
            <td class="label">Điểm đón:</td>
            <td>{{ $invoice->booking->pickupPoint->name }}</td>
        </tr>
        <tr>
            <td class="label">Địa chỉ đón:</td>
            <td>{{ $invoice->booking->pickupPoint->address }}</td>
        </tr>
        <tr>
            <td class="label">Ngày giờ đi:</td>
            <td>{{ date('H:i d/m/Y', strtotime($invoice->booking->trip->departure_time)) }}</td>
        </tr>
    </table>

    <!-- Chi tiết thanh toán -->
    <div class="section-title">CHI TIẾT THANH TOÁN</div>
    <table>
        <tr>
            <td class="label" width="30%">Số lượng vé:</td>
            <td>{{ count(explode(',', $invoice->booking->seat_numbers)) }} vé</td>
        </tr>
        <tr>
            <td class="label">Giá vé:</td>
            <td>{{ number_format($invoice->booking->trip->ticket_price, 0, ',', '.') }} VNĐ/vé</td>
        </tr>
        <tr>
            <td class="label">Tổng tiền:</td>
            <td class="total">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
        </tr>
        <tr>
            <td class="label">Phương thức:</td>
            <td>
                @if ($invoice->booking->payment_method == 'banking')
                    Chuyển khoản ngân hàng
                @elseif($invoice->booking->payment_method == 'cash')
                    Tiền mặt
                @else
                    {{ $invoice->booking->payment_method }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Trạng thái:</td>
            <td>
                @if ($invoice->status == 'paid')
                    <span style="color: green; font-weight: bold;">Đã thanh toán</span>
                @else
                    <span style="color: orange; font-weight: bold;">Chờ thanh toán</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div style="display: flex; justify-content: space-between; margin-top: 30px;">
            <div style="text-align: center; width: 45%;">
                <div style="font-weight: bold;">KHÁCH HÀNG</div>
                <div style="margin-top: 50px;">{{ $invoice->booking->passenger_name }}</div>
            </div>
            <div style="text-align: center; width: 45%;">
                <div style="font-weight: bold;">ĐẠI DIỆN CÔNG TY</div>
                <div style="margin-top: 50px;">{{ $company['name'] }}</div>
            </div>
        </div>
        <div style="margin-top: 30px; font-size: 12px; color: #666;">
            Cảm ơn quý khách đã sử dụng dịch vụ!<br>
            Hóa đơn có hiệu lực đến ngày {{ date('d/m/Y', strtotime('+30 days')) }}
        </div>
    </div>
</body>

</html>
