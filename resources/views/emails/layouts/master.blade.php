<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'CineDot Cinema')</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, p, a, h1, h2, h3, span { font-family: 'Segoe UI', Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #F4F6FB;
            font-family: 'Roboto', 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        table {
            border-spacing: 0;
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        td {
            padding: 0;
            font-family: 'Roboto', 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
        }
        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }
        p, a, h1, h2, h3, span, div {
            font-family: 'Roboto', 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #F4F6FB;
            padding: 32px 0;
        }
        .main {
            background-color: #FFFFFF;
            margin: 0 auto;
            width: 100%;
            max-width: 540px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #EEF2F6;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }
        .top-accent {
            height: 4px;
            background: linear-gradient(90deg, #7C6FE8 0%, #6366F1 100%);
            font-size: 1px;
            line-height: 1px;
        }
        .header {
            background-color: #FFFFFF;
            padding: 24px 30px 18px 30px;
            text-align: center;
        }
        .logo-text {
            font-size: 24px;
            font-weight: 900;
            color: #0F172A;
            letter-spacing: 2px;
            text-decoration: none;
            display: inline-block;
        }
        .logo-dot {
            color: #7C6FE8;
        }
        .logo-sub {
            font-size: 11px;
            font-weight: 600;
            color: #94A3B8;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }
        .content {
            padding: 0 30px 30px 30px;
        }
        .footer {
            background-color: #FAFAFC;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #F1F5F9;
            color: #94A3B8;
            font-size: 12px;
            line-height: 1.6;
        }
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #7C6FE8 0%, #6366F1 100%);
            background-color: #7C6FE8;
            color: #FFFFFF !important;
            padding: 13px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            box-shadow: 0 4px 14px rgba(124, 111, 232, 0.35);
        }
        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 10px 0 !important;
            }
            .main {
                width: 94% !important;
                border-radius: 16px !important;
            }
            .header {
                padding: 18px 16px 14px 16px !important;
            }
            .content {
                padding: 0 16px 24px 16px !important;
            }
            .footer {
                padding: 18px 16px !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#F4F6FB;font-family:'Roboto','Segoe UI',-apple-system,BlinkMacSystemFont,Arial,sans-serif;color:#0F172A;">
    <center class="wrapper">
        <table class="main" width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
            <!-- TOP ACCENT LINE -->
            <tr>
                <td class="top-accent" style="height:4px;background:linear-gradient(90deg, #7C6FE8 0%, #6366F1 100%);"></td>
            </tr>

            <!-- HEADER LOGO -->
            <tr>
                <td class="header" align="center" style="padding:24px 30px 18px 30px;">
                    <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}" class="logo-text" style="font-size:24px;font-weight:900;color:#0F172A;letter-spacing:2px;text-decoration:none;">
                        CINE<span class="logo-dot" style="color:#7C6FE8;">DOT</span>
                    </a>
                    <div class="logo-sub" style="font-size:11px;font-weight:600;color:#94A3B8;letter-spacing:0.5px;margin-top:3px;">Hệ Thống Rạp Chiếu Phim Hiện Đại</div>
                </td>
            </tr>

            <!-- CONTENT -->
            <tr>
                <td class="content" style="padding:0 30px 30px 30px;">
                    @yield('content')
                </td>
            </tr>

            <!-- FOOTER -->
            <tr>
                <td class="footer" align="center" style="background-color:#FAFAFC;padding:20px 30px;border-top:1px solid #F1F5F9;color:#94A3B8;font-size:12px;line-height:1.6;">
                    <p style="margin:0 0 4px 0;color:#475569;font-weight:600;">CineDot Cinema Vietnam</p>
                    <p style="margin:0 0 6px 0;font-size:12px;color:#64748B;">Hotline hỗ trợ: <strong style="color:#0F172A;">1900 6868</strong> &bull; Email: <a href="mailto:support@cinedot.vn" style="color:#7C6FE8;text-decoration:none;font-weight:600;">support@cinedot.vn</a></p>
                    <p style="margin:0;font-size:11px;color:#94A3B8;">&copy; {{ date('Y') }} CineDot. Mọi quyền được bảo lưu.</p>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
