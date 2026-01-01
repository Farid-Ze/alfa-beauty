<?php

namespace Tests\Feature;

use App\Livewire\LoginPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_login_page_contains_livewire_component(): void
    {
        $response = $this->get('/login');
        $response->assertSeeLivewire(LoginPage::class);
    }

    public function test_login_requires_email(): void
    {
        Livewire::test(LoginPage::class)
            ->set('email', '')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);
    }

    public function test_login_requires_valid_email(): void
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'not-an-email')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_login_requires_password(): void
    {
        Livewire::test(LoginPage::class)
            ->set('email', 'test@example.com')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password' => 'required']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'loyalty_tier_id' => 1,
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'loyalty_tier_id' => 1,
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/');
        
        $this->assertAuthenticatedAs($user);
    }
}
