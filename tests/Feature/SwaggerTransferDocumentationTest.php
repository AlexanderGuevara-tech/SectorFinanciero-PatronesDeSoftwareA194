<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwaggerTransferDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_swagger_ui_requires_an_authenticated_account_manager(): void
    {
        $this->get('/api/documentation')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->get('/api/documentation')->assertForbidden();

        $role = Role::create(['name' => 'swagger-documentation-manager']);
        $permission = Permission::firstOrCreate(['name' => 'manage-accounts']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/api/documentation')
            ->assertOk()
            ->assertSee('Swagger UI');
    }

    public function test_transfer_openapi_documentation_describes_web_security_and_validation(): void
    {
        $this->artisan('l5-swagger:generate')->assertSuccessful();

        $user = User::factory()->create();
        $role = Role::create(['name' => 'swagger-documentation-reader']);
        $permission = Permission::firstOrCreate(['name' => 'manage-accounts']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/docs')->assertOk()
            ->assertJsonPath('paths./accounts/transfer.post.requestBody.required', true)
            ->assertJsonPath('paths./accounts/transfer.post.security.0.sessionCookie', [])
            ->assertJsonPath('components.securitySchemes.sessionCookie.in', 'cookie')
            ->assertJsonPath('paths./accounts/transfer.post.parameters.0.name', 'X-CSRF-TOKEN');
    }
}
