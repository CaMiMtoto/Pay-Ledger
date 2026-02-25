<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Payment Reminder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Google+Sans+Code:ital,wght@0,300..800;1,300..800&family=Google+Sans+Flex:opsz,wght@6..144,1..1000&display=swap"
        rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            background: #f5f7fb;
            font-family: 'Google Sans Code', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
        }

        .email-container {
            background: #ffffff;
            border-radius: 10px;
            max-width: 600px;
            width: 100%;
            margin: 24px auto;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .header {
            background: #2984D1;
            color: #ffffff;
            text-align: center;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
        }

        .content {
            padding: 28px 32px;
            font-size: 16px;
            line-height: 1.6;
        }

        .amount-card {
            background: #f0f6fb;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 18px;
            margin: 20px 0;
            text-align: center;
        }

        .amount-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .amount-value {
            color: #2984D1;
            font-size: 24px;
            font-weight: 700;
        }

        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background: #2984D1;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
            border-radius: 6px;
            font-size: 16px;
            margin-top: 16px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            padding: 20px;
            background: #f9fafb;
        }

        @media only screen and (max-width: 620px) {
            .content {
                padding: 20px !important;
                font-size: 15px !important;
            }

            .amount-value {
                font-size: 20px !important;
            }

            .cta-button {
                width: 100% !important;
                box-sizing: border-box;
                text-align: center;
            }
        }

        .logo {
            height: 50px;
            text-align: center !important;
        }
    </style>
</head>
<body>
<div style="text-align: center;margin-bottom: 5px;margin-top: 5px">
    <img src="{{ asset('assets/logos/logo_sm.png') }}" class="logo"/>
</div>
<div class="email-container">
    <div class="header">
        Payment Reminder
    </div>

    <div class="content">
        <p>Hello <strong>{{ $customer->name ?? 'Customer' }}</strong>,</p>

        <p>This is a friendly reminder that you currently have overdue payments on your account.</p>

        <div class="amount-card">
            <div class="amount-label">Total Overdue Amount</div>
            <div class="amount-value">
                {{ number_format($totalOverdue, 2) }}
            </div>
        </div>

        <p>Please arrange payment as soon as possible.</p>

        <p style="margin-top:24px;">If you have already made this payment, please disregard this message.</p>

        <p>Thank you.</p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} RURA. All rights reserved.<br>
        This is an automated reminder. Please do not share sensitive information via email.
    </div>

</div>

</body>
</html>
