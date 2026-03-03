<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSource - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    screens: {
                        'xs': '376px', 'sm': '640px', 'md': '768px',
                        'lg': '1024px', 'xl': '1280px', '2xl': '1536px',
                    },
                },
            },
        }
    </script>

    <style>
        *, *::before, *::after { font-family: 'Manrope', sans-serif; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; }

        .form-input {
            width: 100%; height: 46px; padding: 0 16px;
            border-radius: 8px; border: 1px solid rgba(45,45,45,0.50);
            background: #FFFFFF; font-size: 14px; font-weight: 300; color: #2D2D2D;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input::placeholder { color: rgba(45,45,45,0.30); font-weight: 300; }
        .form-input:focus { outline: none; border-color: #3B7DED; box-shadow: 0 0 0 3px rgba(59,125,237,0.15); }

        .btn-primary {
            width: 70%; height: 45px; border-radius: 12px;
            background-color: #3B7DED; border: 1px solid transparent;
            cursor: pointer; color: #FFFFFA; font-family: 'Manrope', sans-serif;
            font-size: 14px; font-weight: 700; letter-spacing: 0.12em;
            text-transform: uppercase; transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.10), 0 2px 4px -1px rgba(0,0,0,0.06);

            display: block;        /* required for margin auto */
            margin: 30px auto 0 auto;        /* centers horizontally */
        }
        .btn-primary:hover {
            background-color: #FFFFFF; color: #3B7DED;
            border-color: rgba(45,45,45,0.15);
        }

        .page-wrapper {
            width: 100%; height: 100vh; padding: 24px;
            background-color: #FFFFFF; display: flex;
            flex-direction: row; gap: 24px;
        }
        .left-col {
            flex: 1; border-radius: 24px;
            background: linear-gradient(160deg, #C8DCFE 0%, #3B7DED 100%);
            display: flex; flex-direction: column;
            justify-content: space-between; padding: 40px; overflow: hidden;
        }
        .right-col {
            flex: 1; border-radius: 24px; background-color: #FFFFFF;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px; overflow: hidden;
        }
        .deco-asterisk {
            font-size: 26px; font-weight: 700;
            color: rgba(255,255,250,0.80); line-height: 1; user-select: none;
        }
        .text-link { font-size: 14px; font-weight: 300; color: #3B7DED; text-decoration: none; }
        .text-link:hover { text-decoration: underline; }

        @media (max-width: 767px) {
            .page-wrapper { flex-direction: column; height: auto; min-height: 100vh; padding: 16px; gap: 16px; }
            .left-col { flex: none; min-height: 180px; padding: 24px; border-radius: 16px; }
            .right-col { flex: 1; padding: 32px 20px; border-radius: 16px; }
            .form-inner { max-width: 100% !important; }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <!-- ===== LEFT COLUMN ===== -->
    <div class="left-col">
        <div style="display:flex; justify-content:flex-end;">
            <span class="deco-asterisk" style="font-size:82px;">*</span>
        </div>
        <div>
            <h1 style="font-size:52px; font-weight:700; color:#FFFFFA; line-height:1.2; margin:0 0 12px 0;">Welcome!</h1>
            <p style="font-size:20px; font-weight:300; color:rgba(255,255,250,0.80); line-height:1.65; margin:0; max-width:320px;">
                Streamline your HR tasks and <br/> stay connected with your <br/> team, all in one place.
            </p>
        </div>
    </div>

    <!-- ===== RIGHT COLUMN ===== -->
    <div class="right-col">
        <div class="form-inner" style="width:100%; max-width:420px;">

            <!-- Logo -->
            <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:28px;">
                <img src="{{ asset('images/place_holder.png') }}" alt="MediSource Logo"
                     style="width:80px; height:56px; object-fit:contain;"
                     onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex';">
                <div id="logo-fallback" style="display:none; width:80px; height:56px; border:2px solid #2D2D2D; border-radius:2px;">
                    <svg width="80" height="56" viewBox="0 0 80 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="1" y="1" width="78" height="54" stroke="#2D2D2D" stroke-width="1.5"/>
                        <line x1="1" y1="1" x2="79" y2="55" stroke="#2D2D2D" stroke-width="1.5"/>
                        <line x1="79" y1="1" x2="1" y2="55" stroke="#2D2D2D" stroke-width="1.5"/>
                    </svg>
                </div>
                <span style="font-size:18px; font-weight:600; color:#2D2D2D; margin-top:8px; text-transform:uppercase;">
                    MediSource
                </span>
            </div>

            <!-- Heading -->
            <div style="text-align:center; margin-bottom:28px;">
                <h2 style="font-size:32px; font-weight:700; color:#2D2D2D; margin:0 0 6px 0;">Forgot Password</h2>
                <p style="font-size:16px; font-weight:300; color:rgba(45,45,45,0.80); margin:0;">
                    If you forgot your password, please <br/> enter your email below and we will<br/> send you a recovery link.
                </p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div style="margin-bottom:16px; font-size:14px; font-weight:500; color:#3B7DED;">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('password.email') }}" style="display:flex; flex-direction:column; gap:20px;">
                @csrf

                <!-- Email -->
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label for="email" style="font-size:16px; font-weight:500; color:#2D2D2D;">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        required
                        autofocus
                        class="form-input">
                    @error('email')
                        <span style="font-size:13px; color:#EF4444; font-weight:300;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-primary">SEND RECOVERY LINK</button>

                <!-- Back to Sign In -->
                <div style="text-align:center;">
                    <span style="font-size:14px; font-weight:300; color:#2D2D2D;">Remember your password? </span>
                    <a href="{{ route('login') }}" class="text-link">Sign in</a>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>