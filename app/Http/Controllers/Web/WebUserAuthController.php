<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WebUserAuthController extends Controller
{
    public function showAuthForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('user.profile');
        }
        return view('auth.user-auth', [
            'initialTab' => $request->query('mode', 'login'),
        ]);
    }

    public function profile()
    {
        $history = WatchHistory::where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('auth.user-profile', [
            'user' => Auth::user(),
            'history' => $history,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()
            ->withErrors(['email' => 'Email හෝ Password වැරදියි.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}