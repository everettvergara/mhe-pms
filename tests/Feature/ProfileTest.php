<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertNotFound();
    }

    public function test_profile_update_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertNotFound();
    }

    public function test_profile_delete_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', [
                'password' => 'Password1!',
            ])
            ->assertNotFound();

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_picture_url_returns_null_when_file_is_missing(): void
    {
        $user = User::factory()->create();
        $user->update(['profile_picture' => 'profile-pictures/'.$user->id.'/missing.jpg']);

        $this->assertNull($user->profilePictureUrl());
    }
}
