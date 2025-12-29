{{-- resources/views/email.blade.php --}}
<p>Xin chào {{ $user->full_name }},</p>

<p>Bạn đã yêu cầu cấp lại mật khẩu cho tài khoản {{ $user->email }}.</p>

<p><strong>Mật khẩu mới của bạn:</strong></p>

<h2 style="color: red; font-family: monospace;">{{ $newPassword }}</h2>

<p>Vui lòng sử dụng mật khẩu này để đăng nhập.</p>

<p>Sau khi đăng nhập thành công, hãy đổi mật khẩu mới trong phần cài đặt tài khoản.</p>

<hr>

<p>Trân trọng,<br>
    Bus Ecommerce Team</p>
