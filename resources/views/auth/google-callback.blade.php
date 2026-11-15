<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autentikasi Google</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #0f172a;
            color: #f8fafc;
            text-align: center;
            padding: 1rem;
        }
        .box {
            max-width: 320px;
        }
        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid rgba(255, 255, 255, 0.15);
            border-top-color: #06b6d4;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .text {
            font-size: 0.95rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <div class="text">
            {{ $status === 'success' ? 'Berhasil masuk. Mengalihkan...' : ($message ?? 'Terjadi kesalahan.') }}
        </div>
    </div>

    <script>
        (function() {
            var status = @json($status);
            var redirectUrl = @json($redirectUrl);
            var message = @json($message ?? '');

            if (redirectUrl) {
                redirectUrl = redirectUrl.replace(/\/public(\/|$)/, '$1');
            }

            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.postMessage({
                        type: status === 'success' ? 'google-auth-success' : 'google-auth-error',
                        url: redirectUrl,
                        message: message
                    }, window.location.origin);
                    window.close();
                    return;
                } catch (e) {
                    // postMessage fallback
                }
            }

            window.location.href = redirectUrl;
        })();
    </script>
</body>
</html>
