<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSource - Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { manrope: ['Manrope', 'sans-serif'] },
                    colors: {
                        accent: '#3B7DED',
                        'content-dark': '#2D2D2D',
                        'content-light': '#FFFFFF',
                        'bg-surface': '#FFFFFF',
                        'bg-base': '#FFFFFF',
                    },
                    borderRadius: {
                        'sm': '2px',
                        DEFAULT: '4px',
                        'md': '6px',
                        'lg': '8px',
                        'xl': '12px',
                        '2xl': '16px',
                        '3xl': '24px',
                    },
                    screens: {
                        'xs': '376px',
                        'sm': '640px',
                        'md': '768px',
                        'lg': '1024px',
                        'xl': '1280px',
                        '2xl': '1536px',
                    },
                    boxShadow: {
                        'md': '0 4px 6px -1px rgba(0,0,0,0.10), 0 2px 4px -1px rgba(0,0,0,0.06)',
                        'lg': '0 10px 15px -3px rgba(0,0,0,0.10), 0 4px 6px -2px rgba(0,0,0,0.05)',
                    }
                },
            },
        }
    </script>

    <style>
        *, *::before, *::after {
            font-family: 'Manrope', sans-serif;
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        .form-input {
            width: 100%;
            height: 46px;
            padding: 0 16px;
            border-radius: 8px;
            border: 1px solid rgba(45, 45, 45, 0.50);
            background: #FFFFFF;
            font-size: 14px;
            font-weight: 300;
            color: #2D2D2D;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input::placeholder {
            color: rgba(45, 45, 45, 0.30);
            font-weight: 300;
        }
        .form-input:focus {
            outline: none;
            border-color: #3B7DED;
            box-shadow: 0 0 0 3px rgba(59, 125, 237, 0.15);
        }

        .btn-primary {
            width: 70%;
            height: 45px;
            border-radius: 12px;
            background-color: #3B7DED;
            border: 1px solid transparent;
            cursor: pointer;
            color: #FFFFFA;
            font-family: 'Manrope', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.10), 0 2px 4px -1px rgba(0,0,0,0.06);
            display: block;
            margin: 30px auto 0 auto;
        }
        .btn-primary:hover {
            background-color: #FFFFFF;
            color: #3B7DED;
            border-color: rgba(45,45,45,0.15);
        }

        .eye-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            color: rgba(45, 45, 45, 0.40);
            transition: color 0.2s;
        }
        .eye-btn:hover { color: rgba(45, 45, 45, 0.70); }

        .deco-asterisk {
            font-size: 26px;
            font-weight: 700;
            color: rgba(255, 255, 250, 0.80);
            line-height: 1;
            user-select: none;
        }

        .text-link {
            font-size: 14px;
            font-weight: 300;
            color: #3B7DED;
            text-decoration: none;
        }
        .text-link:hover { text-decoration: underline; }

        /* ===== DESKTOP LAYOUT ===== */
        .page-wrapper {
            width: 100%;
            height: 100vh;
            padding: 24px;
            background-color: #FFFFFF;
            display: flex;
            flex-direction: row;
            gap: 24px;
        }

        .left-col {
            flex: 1;
            border-radius: 24px;
            background: linear-gradient(160deg, #C8DCFE 0%, #3B7DED 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            overflow: hidden;
        }

        .right-col {
            flex: 1;
            border-radius: 24px;
            background-color: #FFFFFF;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            overflow: hidden;
        }

        /* ===== MOBILE LAYOUT ===== */
        @media (max-width: 767px) {
            .page-wrapper {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
                padding: 16px;
                gap: 0;
            }
            .left-col {
                flex: none;
                min-height: 300px;
                padding: 24px;
                border-radius: 20px;
            }
            .left-col h1 {
                font-size: 36px !important;
            }
            .left-col p {
                font-size: 16px !important;
            }
            .right-col {
                flex: 1;
                padding: 32px 20px 40px;
                border-radius: 0;
                justify-content: flex-start;
            }
            .form-inner {
                max-width: 100% !important;
            }
            .page-heading {
                font-size: 28px !important;
            }
            .btn-primary {
                width: 100% !important;
                margin-top: 20px !important;
            }
        }

        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-4px); }
            40% { transform: translateX(4px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
            100% { transform: translateX(0); }
        }
        .shake { animation: shake 0.3s ease-in-out; }
        .input-error {
            border-color: #EF4444;
            box-shadow: 0 0 0 1px #EF4444;
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
            <h1 style="font-size:52px; font-weight:700; color:#FFFFFA; line-height:1.2; margin:0 0 12px 0;">
                Welcome!
            </h1>
            <p style="font-size:20px; font-weight:300; color:rgba(255,255,250,0.80); line-height:1.65; margin:0; max-width:320px;">
                Streamline your HR tasks and <br/> stay connected with your <br/> team, all in one place.
            </p>
        </div>

    </div>

    <!-- ===== RIGHT COLUMN ===== -->
    <div class="right-col">

        <div class="form-inner" style="width:100%; max-width:420px;">

            <!-- Logo -->
            <div style="display:flex; justify-content:center; margin-bottom:28px;">
                <img src="{{ asset('images/HRISLogo-Primary.png') }}" alt="HRIS Logo" style="width:180px; height:auto; object-fit:contain;">
            </div>

            <!-- Heading -->
            <div style="text-align:center; margin-bottom:28px;">
                <h2 class="page-heading" style="font-size:32px; font-weight:700; color:#2D2D2D; margin:0 0 8px 0;">
                    Reset Password
                </h2>
                <p style="font-size:16px; font-weight:300; color:rgba(45,45,45,0.80); margin:0; line-height:1.5;">
                    Enter your email and create a new password below.
                </p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('password.update') }}" style="display:flex; flex-direction:column; gap:20px;">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

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
                        autocomplete="email"
                        class="form-input @error('email') input-error shake @enderror">
                    @error('email')
                        <span style="font-size:13px; color:#EF4444; font-weight:300;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- New Password -->
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label for="password" style="font-size:16px; font-weight:500; color:#2D2D2D;">New Password</label>
                    <div style="position:relative;">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter new password"
                            required
                            autocomplete="new-password"
                            class="form-input @error('password') input-error shake @enderror"
                            style="padding-right:44px;">
                        <button type="button" class="eye-btn" onclick="togglePwd('password','eye-pw')" tabindex="-1">
                            <svg id="eye-pw" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span style="font-size:13px; color:#EF4444; font-weight:300;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label for="password_confirmation" style="font-size:16px; font-weight:500; color:#2D2D2D;">Confirm Password</label>
                    <div style="position:relative;">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm new password"
                            required
                            autocomplete="new-password"
                            class="form-input"
                            style="padding-right:44px;">
                        <button type="button" class="eye-btn" onclick="togglePwd('password_confirmation','eye-confirm')" tabindex="-1">
                            <svg id="eye-confirm" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-primary">RESET PASSWORD</button>

                <!-- Back to Sign In -->
                <div style="text-align:center; margin-top:8px;">
                    <span style="font-size:14px; font-weight:300; color:#2D2D2D;">Remember your password? </span>
                    <a href="{{ route('login') }}" class="text-link">Sign in</a>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
    function togglePwd(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.126-3.36M6.108 6.108A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.423 5.276M15 12a3 3 0 11-4.243-4.243M3 3l18 18"/>
            `;
        } else {
            input.type = 'password';
            icon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            `;
        }
    }
</script>

</body>
</html>