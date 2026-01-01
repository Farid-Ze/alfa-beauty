<?php

namespace Tests\Feature;

use App\Livewire\RegisterPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
    }

    public function test_register_page_renders(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_register_page_contains_livewire_component(): void
    {
        $response = $this->get('/register');
        $response->assertSeeLivewire(RegisterPage::class);
    }

    public function test_register_requires_name(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', '')
            ->set('company_name', 'Test Company')
            ->set('email', 'test@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['name']);
    }

    public function test_register_requires_company_name(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', '')
            ->set('email', 'test@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['company_name']);
    }

    public function test_register_requires_valid_email(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'not-an-email')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
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

    public function test_register_requires_phone(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'test@example.com')
            ->set('phone', '')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['phone']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'test@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different-password')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_register_requires_minimum_password_length(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'Test User')
            ->set('company_name', 'Test Company')
            ->set('email', 'test@example.com')
            ->set('phone', '08123456789')
            ->set('password', '123')
            ->set('password_confirmation', '123')
            ->call('register')
            ->assertHasErrors(['password']);
    }

    public function test_register_succeeds_with_valid_data(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'New User')
            ->set('company_name', 'New Company')
            ->set('email', 'newuser@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'Password123!')  // Strong password
            ->set('password_confirmation', 'Password123!')
            ->call('register')
            ->assertRedirect(route('verification.notice'));
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'company_name' => 'New Company',
        ]);
        
        $this->assertAuthenticated();
    }

    public function test_registered_user_gets_guest_tier(): void
    {
        Livewire::test(RegisterPage::class)
            ->set('name', 'New User')
            ->set('company_name', 'New Company')
            ->set('email', 'tiertest@example.com')
            ->set('phone', '08123456789')
            ->set('password', 'Password123!')  // Strong password
            ->set('password_confirmation', 'Password123!')
            ->call('register');
        
        $user = User::where('email', 'tiertest@example.com')->first();
        
        $this->assertNotNull($user);
        $this->assertEquals(0, $user->points);
        $this->assertEquals(0, $user->total_spend);
    }
}
