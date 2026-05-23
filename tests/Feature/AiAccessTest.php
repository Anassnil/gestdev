<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AiAccessTest extends TestCase
{
    use RefreshDatabase;
    public function test_guest_redirected_to_login()
    {
        $response = $this->get('/ai/models');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_ai_routes()
    {
        // define gate to allow manage-ai for current user
        Gate::define('manage-ai', function ($user) {
            return true;
        });

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/ai/models');
        $response->assertStatus(200);
    }
}
