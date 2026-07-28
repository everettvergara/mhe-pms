<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/change-password');

        $response->assertOk();
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/change-password')
            ->put('/password', [
                'current_password' => 'Password1!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/change-password');

        $this->assertTrue(Hash::check('NewPassword1!', $user->refresh()->password));
    }

    public function test_password_change_is_logged(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/change-password')
            ->put('/password', [
                'current_password' => 'Password1!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'password_change',
            'record_id' => $user->id,
        ]);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/change-password')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/change-password');
    }

    public function test_new_password_must_differ_from_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/change-password')
            ->put('/password', [
                'current_password' => 'Password1!',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirect('/change-password');
    }
}
