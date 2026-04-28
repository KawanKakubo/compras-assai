<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->authenticated(request(), Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->authenticated($request, Auth::user());
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->isAdmin()) {
            return redirect()->intended('/admin/dashboard');
        } elseif ($user->isGabinete()) {
            return redirect()->intended('/gabinete/dashboard');
        } elseif ($user->isCompras()) {
            return redirect()->intended('/compras/dashboard');
        } elseif ($user->isSecretaria()) {
            return redirect()->intended('/secretaria/dashboard');
        } else {
            // Se o cargo for inválido ou antigo (ex: 'secretaria'), desloga e volta pro login
            Auth::logout();
            return redirect()->route('login')->with('error', 'Seu perfil de acesso está desatualizado. Por favor, entre em contato com o administrador.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
