<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .code-container {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .verification-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #007bff;
            margin: 0;
            font-family: 'Courier New', monospace;
        }
        .instructions {
            text-align: center;
            color: #666;
            margin: 20px 0;
        }
        .expiry-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            color: #856404;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Reset Password</h1>
            <p class="instructions">Gunakan kode verifikasi berikut untuk mereset password Anda</p>
        </div>
        
        <div class="code-container">
            <h2 class="verification-code">{{ $token }}</h2>
        </div>
        
        <div class="expiry-notice">
            <strong>⏰ Kode ini berlaku selama 15 menit</strong>
        </div>
        
        <div class="footer">
            <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        </div>
    </div>
</body>
</html>