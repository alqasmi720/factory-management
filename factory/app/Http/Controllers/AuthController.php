<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($credentials, $request->boolean('remember'))) { return back()->withErrors(['email'=>'The sign-in details were not recognized.'])->onlyInput('email'); }
        if (!auth()->user()->active) { Auth::logout(); return back()->withErrors(['email'=>'This staff account is inactive.']); }
        $request->session()->regenerate(); return redirect()->intended('/');
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/login');
    }
}
