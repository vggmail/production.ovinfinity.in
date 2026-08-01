<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'UserName' => 'required|string',
            'Password' => 'required|string',
        ]);

        $user = User::where('UserName', $credentials['UserName'])
                    ->where('IsActive', 1)
                    ->first();

        if ($user) {
            // Verify password (checks plain text first, then standard bcrypt hash)
            $passwordMatch = ($credentials['Password'] === $user->Password) || Hash::check($credentials['Password'], $user->Password);

            if ($passwordMatch) {
                Auth::login($user);
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors([
            'UserName' => 'The provided credentials do not match our records.',
        ])->onlyInput('UserName');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
