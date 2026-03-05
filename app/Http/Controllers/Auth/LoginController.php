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
        // Validate inputs
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Step 1: Check if email exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'No account found with this email.',
            ])->withInput();
        }

        // Step 2: Attempt login
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ])->withInput();
        }

        // Step 3: Successful login
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
                return redirect()->route('payroll.dashboard');
            case 'finance_officer':
                return redirect()->route('finance.dashboard');
            case 'employees':
                return redirect()->route('employee.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account role is not recognized.'
                ]);
        }
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