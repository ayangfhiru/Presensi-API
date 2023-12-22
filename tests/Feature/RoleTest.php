<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testAddRoleSuccess()
    {
        $response = $this->post('api/role', [
            'name' => 'testing'
        ]);
    }
}
