<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class RegisterPage extends Component
{
    public string $name = '';
    public string $company_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules()
    {
        return [
            'name' => 'required|min:3|max:255',
            'company_name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric|min_digits:10|max_digits:15',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(): string
    {
        return 'register:' . request()->ip();
    }

    public function register()
    {
        // Rate limiting: max 3 registrations per minute per IP
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->addError('email', __('auth.throttle', ['seconds' => $seconds]));
            return;
        }

        $this->validate();

        RateLimiter::hit($this->throttleKey());

        // Get Guest tier (default for new users)
        $guestTier = \App\Models\LoyaltyTier::where('slug', 'guest')->first();

        $user = User::create([
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'loyalty_tier_id' => $guestTier?->id ?? 1, // Default to Guest tier
            'points' => 0,
            'total_spend' => 0,
        ]);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        Auth::login($user);

        session()->flash('message', __('auth.verification_sent'));

        return redirect()->route('verification.notice');
    }

    public function render()
    {
        return view('livewire.register-page');
    }
}

