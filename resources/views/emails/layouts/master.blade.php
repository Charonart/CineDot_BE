<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CineDot Cinema')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0B0F19;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #E2E8F0;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-spacing: 0;
            border-collapse: collapse;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #0B0F19;
            padding: 40px 0;
        }
        .main {
            background-color: #151D2E;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #1E293B;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .header {
            background: linear-gradient(135deg, #1E1014 0%, #0F172A 100%);
            padding: 32px 40px;
            text-align: center;
            border-bottom: 2px solid #E50914;
        }
        .logo-text {
            font-size: 28px;
            font-weight: 900;
            color: #FFFFFF;
            letter-spacing: 2px;
            text-decoration: none;
        }
        .logo-dot {
            color: #E50914;
        }
        .content {
            padding: 36px 40px;
        }
        .footer {
            background-color: #0E1422;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #1E293B;
            color: #64748B;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn-primary {
            display: inline-block;
            background-color: #E50914;
            color: #FFFFFF !important;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            text-align: center;
        }
        @media only screen and (max-width: 600px) {
            .main {
                width: 95% !important;
            }
            .header, .content, .footer {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <center class="wrapper">
        <table class="main" width="100%">
            <!-- HEADER -->
            <tr>
                <td class="header">
                    <a href="{{ env('FRONTEND_URL', 'https://cinedot.vn') }}" class="logo-text">
                        CINE<span class="logo-dot">DOT</span>
                    </a>
                </td>
            </tr>

            <!-- CONTENT BODY -->
            <tr>
                <td class="content">
                    @yield('content')
                </td>
            </tr>

            <!-- FOOTER -->
            <tr>
                <td class="footer">
                    <p style="margin: 0 0 8px 0;">Cảm ơn bạn đã đồng hành cùng hệ thống rạp chiếu phim <strong>CineDot</strong>.</p>
                    <p style="margin: 0 0 12px 0;">Hotline hỗ trợ: <strong style="color: #94A3B8;">1900 6868</strong> | Email: <a href="mailto:support@cinedot.vn" style="color: #E50914; text-decoration: none;">support@cinedot.vn</a></p>
                    <p style="margin: 0; font-size: 11px; color: #475569;">&copy; {{ date('Y') }} CineDot Cinema Ecosystem. Mọi quyền được bảo lưu.</p>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
