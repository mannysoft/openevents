<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_users_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'organiser@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'organiser@example.com')
            ->set('password', 'secret-password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'organiser@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'organiser@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'New Organiser')
            ->set('email', 'new@example.com')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertRedirect(route('admin.organisers.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Register::class)
            ->set('name', 'New Organiser')
            ->set('email', 'taken@example.com')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasErrors('email');
    }

    public function test_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
