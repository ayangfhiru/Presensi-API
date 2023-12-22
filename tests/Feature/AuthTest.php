<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
 
    use RefreshDatabase;

    // Register
    public function testRegisterSuccess()
    {
        $response = $this->post('api/users/register', [
            'username' => 'ghege',
            'email' => 'ghege@example.com',
            'phone' => '089599394',
            'password' => 'rahasia',
            'role_id' => 1,
            'division_id' => null
        ]);
        $response->assertJson([
            'data' => [
                'username' => 'ghege',
                'email' => 'ghege@example.com',
                'phone' => '089599394',
                'role' => 1,
                'division' => null
            ]
        ]);
        $response->assertStatus(201);
    }

    public function testRegisterFailed()
    {
        $response = $this->post('api/users/register', [
            'username' => '',
            'email' => '',
            'phone' => '',
            'password' => null,
            'role_id' => null,
            'division_id' => null
        ]);
        $response->assertJson([
            'errors' => [
                'username' => [
                    'The username field is required.'
                ],
                'email' => [
                    'The email field is required.'
                ],
                // 'phone' => [
                //     'The phone field must not be greater than 20 characters.'
                // ],
                'password' => [
                    'The password field is required.'
                ],
                'role_id' => [
                    'The role id field is required.'
                ]
            ]
        ]);
        $response->assertStatus(400);
    }

    public function testRegisterAlredyExists()
    {
        $this->testRegisterSuccess();

        $response = $this->post('api/users/register', [
            'username' => 'ghege',
            'email' => 'ghege@example.com',
            'phone' => '089599394',
            'password' => 'rahasia',
            'role_id' => 1,
            'division_id' => null
        ]);

        $response->assertJson([
            'errors' => [
                'username' => [
                    'username alredy registered'
                ],
                // 'email' => [
                //     'email alredy registered'
                // ],
                // 'phone' => [
                //     'phone alredy registered'
                // ]
            ]
        ]);
        $response->assertStatus(400);
    }

    // Login
    public function testLoginSuccess()
    {
        $this->seed([UserSeeder::class]);

        $resposne = $this->post('api/users/login', [
            'email' => 'ghege@example.com',
            'password' => 'rahasia'
        ]);

        $resposne->assertJson([
            'data' => [
                'username' => 'ghege',
                'email' => 'ghege@example.com',
                'phone' => '0895667663',
                'role' => 1,
                'division' => null
            ]
        ]);
        $resposne->assertStatus(200);
    }

    public function testLoginFailed()
    {
        $response = $this->post('/api/users/login', [
            'email' => 'ghegee@xample.com',
            'password' => 'benar'
        ]);
        $response->assertJson([
            'errors' => [
                'message' => [
                    'email or password wrong'
                ]
            ]
        ]);
        $response->assertStatus(401);
    }

    public function testLoginUserNotFound()
    {
        $response = $this->post('/api/users/login', [
            'email' => 'fhiru@example.com',
            'password' => 'nopassword'
        ]);
        $response->assertJson([
            'errors' => [
                'message' => [
                    'email or password wrong'
                ]
            ]
        ]);
        $response->assertStatus(401);
    }

    public function testLoginPasswordWrong()
    {
        $response = $this->post('/api/users/login', [
            'email' => 'ghege@example.com',
            'password' => 'nopassword'
        ]);
        $response->assertJson([
            'errors' => [
                'message' => [
                    'email or password wrong'
                ]
            ]
        ]);
        $response->assertStatus(401);
    }

    public function testGetUserSucces()
    {
        $this->seed([UserSeeder::class]);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 46|0L3G7vZ2NB7qkl8tYNyqel8hM4NgPa2q5mDX6XD137474408',
            'Accept' => 'application/json'
        ])
            ->get('/api/users');
        $response->assertJson([
            'data' => [
                'username' => 'ghege',
                'email' => 'email'
            ]
        ]);
        $response->assertStatus(200);
    }

    public function testGetUserUnauthorized()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer 46|0L3G7vZ2NB7qkl8tYNyqel8hM4NgPa2q5mDX6XD137474408',
            'Accept' => 'application/json'
        ])->get('/api/users');
        $response->assertJson([
            'errors' => [
                'message' => [
                    'Unauthenticated.'
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    // Update
    public function testUpdateUser()
    {
        $response = $this->patch('/api/users', [
            'phone' => '08996532454'
        ]);

        $response->assertJson([
            'data' => [
                'username' => 'ghege',
                'email' => 'ghege@example.com',
                'phone' => '0895667663',
                'role' => 1,
                'division' => null
            ]
        ]);

        $response->assertStatus(200);
    }
}
