<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class LoginPage extends Component
{
    public string $email = '';
    public string $password = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }

    public function login()
    {
        $this->validate();

        // Rate limiting: max 5 attempts per minute
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->addError('email', __('auth.throttle', ['seconds' => $seconds]));
            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::clear($this->throttleKey());
            session()->regenerate();
            return redirect()->intended('/');
        }

        RateLimiter::hit($this->throttleKey());
        $this->addError('email', __('auth.failed'));
    }

    public function render()
    {
        return view('livewire.login-page');
    }
}
