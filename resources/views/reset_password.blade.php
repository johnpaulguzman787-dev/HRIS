<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSource - Reset Password</title>
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
        <form method="POST" action="{{ route('password.update') }}" style="width:100%; max-width:400px;">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div style="margin-bottom:16px;">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
                @error('email') <div style="color:red;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label>New Password</label>
                <input type="password" name="password" required class="form-input">
                @error('password') <div style="color:red;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom:16px;">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required class="form-input">
            </div>

            <button type="submit" class="btn-primary">Reset Password</button>

        </form>
    </div>
</body>
</html>