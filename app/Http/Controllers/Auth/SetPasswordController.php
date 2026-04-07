<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class SetPasswordController extends Controller
{
    public function showForm(string $token)
    {
        return view('set_password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->save();

                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password set! You can now log in.')
            : back()->withInput()->withErrors(['email' => __($status)]);
    }
}
