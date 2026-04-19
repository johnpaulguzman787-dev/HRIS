<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        return view('login_page'); // your Blade
    }

    // Handle login
    public function login(Request $request)
    {
        // Step 1: Validate email only first
        $request->validate(['email' => 'required|email']);

        // Step 2: Check if email exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'No account found with this email.',
            ])->withInput();
        }

        // Step 3: Check verification before asking for password
        if (is_null($user->email_verified_at)) {
            return back()->withErrors([
                'email' => 'Your account is not yet verified. Please check your email for the verification link.',
            ])->with('unverified_email', $request->email)->withInput();
        }

        // Step 4: Now validate password and attempt login
        $request->validate(['password' => 'required']);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ])->withInput();
        }

// Step 4: Successful login
$request->session()->regenerate();

return $this->redirectToRole(Auth::user());
    }

    // Role-based redirect
    protected function redirectToRole($user)
    {
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'hr_manager':
                return redirect()->route('hr.dashboard');
            case 'supervisor':
                return redirect()->route('supervisor.dashboard');
            case 'payroll_officer':
                return redirect()->route('payroll_officer.dashboard');
            case 'finance_officer':
                return redirect()->route('finance_officer.dashboard');
            case 'employee':
                return redirect()->route('employee.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account role is not recognized.'
                ]);
        }
    }

    // Resend verification email from login page
    public function resendVerificationFromLogin(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with this email.'], 404);
        }

        if (!is_null($user->email_verified_at)) {
            return response()->json(['success' => false, 'message' => 'This email is already verified. You can log in now.'], 422);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            \Log::error('Resend verification failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email. Please try again.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Verification email resent! Please check your inbox.']);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}