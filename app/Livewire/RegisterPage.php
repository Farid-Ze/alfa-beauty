<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RegisterPage extends Component
{
    public $name;
    public $company_name;
    public $email;
    public $phone;
    public $password;
    public $password_confirmation;

    protected $rules = [
        'name' => 'required|min:3',
        'company_name' => 'required|min:3',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|numeric|min_digits:10',
        'password' => 'required|min:6|confirmed',
    ];

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

        Auth::login($user);

        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.register-page');
    }
}

