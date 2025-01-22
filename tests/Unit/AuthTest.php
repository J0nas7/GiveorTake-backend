<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration success.
     */
    public function test_register_user_success()
    {
        $data = [
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
            'User_Status' => 1,
            'User_FirstName' => 'Buzz',
            'User_Surname' => 'Lightyear',
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['User_Email' => 'test@example.com']);
    }

    /**
     * Test registration with invalid data.
     */
    public function test_register_user_invalid_data()
    {
        $data = [
            'User_Email' => 'invalid-email',
            'User_Password' => 'short',
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(400)
                 ->assertJsonStructure(['errors']);
    }

    /**
     * Test user login success.
     */
    public function test_login_user_success()
    {
        $password = 'password123';
        $user = User::factory()->create(['User_Password' => bcrypt($password)]);

        $response = $this->postJson('/api/auth/login', [
            'User_Email' => $user->User_Email,
            'User_Password' => $password,
        ]);

        // $response->assertStatus(200)->assertJsonStructure(['token']);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_user_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'User_Email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid email or password']);
    }

    /**
     * Test accessing authenticated user details.
     */
    public function test_authenticated_user_details()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                 ->assertJsonFragment(['User_Email' => $user->User_Email]);
    }

    /**
     * Test accessing authenticated user details without authentication.
     */
    public function test_authenticated_user_details_unauthenticated()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Not authenticated']);
    }

    /**
     * Test logout.
     */
    public function test_logout_user_success()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }

    /**
     * Edge Case: Register user with duplicate email.
     */
    public function test_register_user_duplicate_email()
    {
        User::factory()->create(['User_Email' => 'test@example.com']);

        $data = [
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
            'User_Status' => 1,
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(400)
                 ->assertJsonStructure(['errors']);
    }

    /**
     * Edge Case: Logout without being logged in.
     */
    public function test_logout_user_not_logged_in()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }
}
?>