<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_account_page_is_displayed(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'job_title' => 'developer',
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get(route('account.my'));

        $response->assertOk();
    }

    public function test_my_account_profile_information_can_be_updated(): void
    {
        $employee = Employee::factory()->create([
            'name' => 'Test Employee',
            'job_title' => 'developer',
        ]);

        $user = User::factory()->create([
            'role' => 'employee',
            'employee_id' => $employee->id,
            'must_change_password' => false,
            'email' => 'test.employee@perfection.com',
        ]);

        $response = $this->actingAs($user)->put(route('account.my.update'), [
            'bio' => 'Backend developer',
            'social_link_1' => 'https://linkedin.com/in/test-employee',
            'social_link_2' => 'https://github.com/test-employee',
            'email' => 'should-not-change@perfection.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Backend developer',
            'social_link_1' => 'https://linkedin.com/in/test-employee',
            'social_link_2' => 'https://github.com/test-employee',
        ]);

        $this->assertSame('test.employee@perfection.com', $user->fresh()->email);
    }
}
