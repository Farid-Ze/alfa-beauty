<?php

namespace Tests\Feature;

use App\Livewire\LoginPage;
use App\Livewire\RegisterPage;
use App\Models\User;
use App\Models\LoyaltyTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed loyalty tiers
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
    }

    public function test_user_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_user_can_view_register_page(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Register');
    }

    public function test_user_can_register_with_valid_data(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'test@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect('/');
        
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'company_name' => 'Test Company',
        ]);

        $this->assertAuthenticated();
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'existing@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->get('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_new_user_gets_guest_tier(): void
    {
        $guestTier = LoyaltyTier::where('slug', 'guest')->first();

        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'test@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertEquals($guestTier->id, $user->loyalty_tier_id);
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->total_spend);
    }
}
