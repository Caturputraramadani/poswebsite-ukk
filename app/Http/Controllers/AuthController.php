<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            if ($user->role === 'admin' || $user->role === 'employee') {
                $request->session()->regenerate();
                return redirect()->route('dashboard')->with('success', 'Login successful! Welcome back.');
            }
        }

        return back()->withErrors(['email' => 'Email or password is incorrect'])
                    ->with('error', 'Login failed. Please make sure your email and password are correct.');
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login')->with('success', 'You have been successfully logged out.');
    }
}