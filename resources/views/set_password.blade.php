<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSource - Set Your Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { font-family: 'Manrope', sans-serif; box-sizing: border-box; }
        html, body { margin:0; padding:0; width:100%; height:100%; }
        .form-input { width:100%; height:46px; padding:0 16px; border-radius:8px; border:1px solid rgba(45,45,45,0.50); background:#fff; font-size:14px; color:#2D2D2D; }
        .btn-primary { width:70%; height:45px; border-radius:12px; background:#3B7DED; color:#fff; font-weight:700; margin:20px auto; display:block; text-transform:uppercase; }
    </style>
</head>
<body>
    <div style="display:flex; justify-content:center; align-items:center; height:100vh;">
        <form method="POST" action="{{ route('set-password.submit') }}" style="width:100%; max-width:400px;">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <h2 style="text-align:center; margin-bottom:24px; font-size:22px; font-weight:700; color:#2D2D2D;">Set Your Password</h2>
            <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:24px;">
                Welcome to Medisource HRIS! Please set a password to activate your account.
            </p>

            <div style="margin-bottom:16px;">
                <label>Email</label>
                <input type="email" name="email" value="{{ $email ?? old('email') }}" required class="form-input" readonly>
                @error('email') <div style="color:red; font-size:13px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label>New Password</label>
                <input type="password" name="password" required class="form-input" minlength="8">
                @error('password') <div style="color:red; font-size:13px; margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required class="form-input" minlength="8">
            </div>

            <button type="submit" class="btn-primary">Activate Account</button>

        </form>
    </div>
</body>
</html>
