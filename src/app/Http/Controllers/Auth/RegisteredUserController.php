<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // 登録完了のウェルカムメールを送る（Docker では mailpit に届く → http://localhost:8025 で確認）
        // Mail::to($user) とすると、宛先にメールアドレスと名前の両方が入る
        // メールサーバーの不調で送れなくても、会員登録自体は完了させたいのでエラーはログに残すだけにする
        try {
            Mail::to($user)->send(new WelcomeMail($user));
        } catch (\Throwable $e) {
            Log::error('Welcome mail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
