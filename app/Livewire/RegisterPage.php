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
    public $business_name;
    public $email;
    public $phone;
    public $password;
    public $password_confirmation;

    protected $rules = [
        'name' => 'required|min:3',
        'business_name' => 'required|min:3',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|numeric|min_digits:10',
        'password' => 'required|min:6|confirmed',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'business_name' => $this->business_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            // Let database use default values for: loyalty_tier_id, points, total_spend
        ]);

        Auth::login($user);

        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.register-page');
    }
}
