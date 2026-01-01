<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class RegisterPage extends Component
{
    public $name;
    public $company_name;
    public $email;
    public $phone;
    public $password;
    public $password_confirmation;

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

    public function register()
    {
        $this->validate();

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

