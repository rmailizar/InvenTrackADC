<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InvenTrack</title>
    <meta name="description" content="Login ke InvenTrack - Sistem Manajemen Inventory">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <!-- Prevent flash: apply theme before render -->
    <script>
        (function () {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body>
    <div class="login-page">
        <!-- Theme Toggle on Login Page -->
        <div class="login-theme-toggle">
            <button class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti tema" id="themeToggle">
                <i class="bi bi-sun-fill icon-sun"></i>
                <i class="bi bi-moon-fill icon-moon"></i>
            </button>
        </div>

        <div class="login-card">
            <div class="login-brand">
                <div class="brand-icon">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <h1>InvenTrack</h1>
                <p>Sistem Manajemen Inventory</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 rounded-3 mb-3"
                    style="font-size:13px; border:none; background:var(--danger-bg); color:var(--danger-dark);">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success py-2 px-3 rounded-3 mb-3"
                    style="font-size:13px; border:none; background:var(--success-bg); color:var(--success-dark);">
                    <i class="bi bi-check-circle me-1"></i>
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="position-relative">
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                            value="{{ old('email') }}" required autofocus id="login-email">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="position-relative">
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password"
                            required id="login-password">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label" for="remember"
                            style="font-size:13px; color:var(--text-secondary);">Ingat Saya</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="login-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Masuk
                </button>
            </form>
            <a class="btn btn-danger mt-4" id="login-submit" href="{{ route('public.stock-request') }}">
                <i class="bi bi-box-arrow-in-left"></i>
                Kembali
            </a>

            <div class="text-center mt-4" style="font-size:12px; color:var(--text-muted);">
                &copy; {{ date('Y') }} InvenTrack. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('inventrack-theme', newTheme);
        }
    </script>
</body>

</html>