<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Medisource HRIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md text-center">

        <!-- Icon -->
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Check your email</h1>
        <p class="text-gray-500 text-sm mb-6">
            We've sent a verification link to your email address. Please click the link to activate your account before logging in.
        </p>

        <!-- Success message -->
        @if (session('message'))
            <div class="mb-4 px-4 py-3 bg-green-50 border border-green-100 text-green-700 text-sm rounded-xl">
                {{ session('message') }}
            </div>
        @endif

        <!-- Resend form -->
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 hover:shadow-lg">
                Resend Verification Email
            </button>
        </form>

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-gray-400 hover:text-gray-600 transition-colors duration-200">
                Back to Login
            </button>
        </form>

    </div>
</body>
</html>