<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Welcome</title>
</head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#071024 0%,#0b3a66 45%,#0ea5a4 100%);font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial;color: #e6f7ff;">

  <!-- Container -->
  <div style="width:min(920px,92%);display:flex;gap:28px;align-items:center;justify-content:space-between;padding:40px;border-radius:20px;box-shadow:0 10px 30px rgba(2,6,23,0.6);background:linear-gradient(180deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.06)">

    <!-- Left: Text -->
    <div style="flex:1;min-width:280px;">
      <h1 style="margin:0 0 10px 0;font-size:46px;line-height:1.02;font-weight:700;letter-spacing:-0.02em;background:linear-gradient(90deg,#dff6ff 0%,#7fe3e3 50%,#34d399 100%);-webkit-background-clip:text;background-clip:text;color:transparent;">Chào mừng đến với API-backend</h1>
      <p style="margin:0 0 22px 0;font-size:16px;opacity:0.92;max-width:56ch;color:rgba(230,247,255,0.95)">Trang này cung cấp giao diện nhẹ, rõ ràng và có nhãn hiệu chuyên nghiệp để giới thiệu dịch vụ backend của bạn. Tập trung vào hiệu năng, dễ tích hợp và bảo mật.</p>

      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a href="#" style="display:inline-flex;align-items:center;gap:10px;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;background:linear-gradient(90deg,#06b6d4,#3b82f6);box-shadow:0 6px 18px rgba(13,60,90,0.35);color:#041022;">Bắt đầu — Tài liệu</a>
        <a href="#" style="display:inline-flex;align-items:center;gap:10px;padding:12px 18px;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:#dff6ff;">Kiểm tra API</a>
      </div>

      <ul style="margin:18px 0 0 0;padding-left:18px;color:rgba(230,247,255,0.9);list-style:none;display:flex;gap:10px;align-items:center">
        <li style="display:flex;align-items:center;gap:8px;font-size:13px;">
          <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#34d399;box-shadow:0 0 8px rgba(52,211,153,0.25)"></span>
          REST + JSON
        </li>
        <li style="display:flex;align-items:center;gap:8px;font-size:13px;">
          <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#60a5fa;box-shadow:0 0 8px rgba(96,165,250,0.18)"></span>
          Authentication
        </li>
        <li style="display:flex;align-items:center;gap:8px;font-size:13px;">
          <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f97316;box-shadow:0 0 8px rgba(249,115,22,0.18)"></span>
          Logs & Metrics
        </li>
      </ul>
    </div>

    <!-- Right: Illustration -->
    <div style="width:360px;flex:0 0 360px;display:flex;align-items:center;justify-content:center;position:relative">

      <!-- Decorative gradient rounded block -->
      <div aria-hidden="true" style="width:320px;height:220px;border-radius:16px;background:linear-gradient(135deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.04);backdrop-filter:blur(6px);box-shadow:0 8px 40px rgba(2,6,23,0.6);display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:22px;">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
          <div style="width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#06b6d4,#3b82f6);box-shadow:0 6px 16px rgba(6,182,212,0.18);font-weight:700;color:#041022">API</div>
          <div style="font-size:14px;font-weight:700;color:rgba(230,247,255,0.95)">Status <span style="font-weight:600;color:#34d399;margin-left:8px">Online</span></div>
        </div>

        <div style="width:100%;display:flex;flex-direction:column;gap:8px">
          <div style="height:8px;border-radius:999px;background:rgba(255,255,255,0.04);overflow:hidden">
            <div style="width:78%;height:100%;background:linear-gradient(90deg,#7fe3e3,#3b82f6);box-shadow:inset 0 2px 8px rgba(59,130,246,0.14)"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:12px;opacity:0.9;color:rgba(230,247,255,0.85)"><span>Throughput</span><span>78%</span></div>
        </div>

      </div>

      <!-- Small floating code box -->
      <pre style="position:absolute;right:-8px;bottom:-16px;margin:0;padding:12px;border-radius:10px;background:rgba(2,6,23,0.65);border:1px solid rgba(255,255,255,0.04);font-family:Menlo, Monaco, monospace;font-size:12px;color:#cdeef7;box-shadow:0 8px 30px rgba(2,6,23,0.6);">GET /v1/health</pre>
    </div>

  </div>

  <!-- Footer small text -->
  <div style="position:fixed;left:12px;bottom:12px;font-size:12px;color:rgba(230,247,255,0.6)">Built for performance ·  Secure by design</div>

</body>
</html>
