<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone_number' => '20123456',
            'country_code' => '+216',
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Ali',
            'city_id' => 1,
            'language' => 'fr',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['otp_sent']
                 ]);

        $this->assertDatabaseHas('users', [
            'phone_number' => '+21620123456',
            'first_name' => 'Ahmed',
        ]);
    }

    /** @test */
    public function user_cannot_register_with_duplicate_phone()
    {
        User::factory()->create([
            'phone_number' => '+21620123456'
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'phone_number' => '20123456',
            'country_code' => '+216',
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Ali',
            'city_id' => 1,
            'language' => 'fr',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'phone_number' => '+21620123456',
            'password' => Hash::make('password123'),
            'phone_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone_number' => '+21620123456',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'access_token',
                         'refresh_token',
                     ]
                 ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'phone_number' => '+21620123456',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone_number' => '+21620123456',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_access_profile()
    {
        $user = User::factory()->create();
        $token = $this->generateToken($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/v1/auth/profile');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => [
                             'id',
                             'first_name',
                             'last_name',
                             'phone_number',
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertStatus(401);
    }

    /**
     * Generate a test JWT token
     */
    protected function generateToken(User $user): string
    {
        $payload = [
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return \Firebase\JWT\JWT::encode(
            $payload,
            config('app.jwt_secret'),
            'HS256'
        );
    }
}
