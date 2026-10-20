<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioClienteLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_customer_on_creation_without_matching_user_id_or_email(): void
    {
        $admin = $this->admin();
        $otherCustomer = Cliente::factory()->create();
        Cliente::factory()->create();
        $customer = Cliente::factory()->create(['email' => 'borrower@example.test']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Borrower',
            'email' => 'different@example.test',
            'password' => 'password123',
            'customer_id' => $customer->id,
        ])->assertRedirect(route('admin.users.index'));

        $borrower = User::where('email', 'different@example.test')->firstOrFail();
        $this->assertNotSame($borrower->id, $customer->id);
        $this->assertSame($customer->id, $borrower->customer->id);
        $this->assertDatabaseHas('users', ['id' => $borrower->id, 'customer_id' => $customer->id]);
        $this->assertNotSame($otherCustomer->id, $borrower->customer_id);
    }

    public function test_admin_can_change_and_remove_customer_without_changing_roles(): void
    {
        $admin = $this->admin();
        $first = Cliente::factory()->create();
        $second = Cliente::factory()->create();
        $user = User::factory()->create(['customer_id' => $first->id]);
        $role = Role::create(['name' => 'borrower']);
        $user->roles()->attach($role);
        $payload = ['name' => $user->name, 'email' => $user->email, 'roles' => [$role->id]];

        $this->actingAs($admin)->put(route('admin.users.update', $user), $payload + ['customer_id' => $second->id])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => $second->id]);
        $this->assertSame($second->id, $user->fresh()->customer->id);

        $this->actingAs($admin)->put(route('admin.users.update', $user), $payload + ['customer_id' => ''])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => null]);
        $this->assertNull($user->fresh()->customer);
        $this->assertTrue($user->fresh()->hasRole('borrower'));
    }

    public function test_existing_user_without_link_remains_unlinked_on_legacy_update(): void
    {
        $admin = $this->admin();
        $customer = Cliente::factory()->create(['email' => 'same@example.test']);
        $user = User::factory()->create(['email' => 'same@example.test']);

        $this->assertNull($user->customer);
        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => null]);
        $this->assertNull($user->fresh()->customer);
    }

    public function test_legacy_update_without_customer_field_preserves_existing_assignment(): void
    {
        $admin = $this->admin();
        $customer = Cliente::factory()->create();
        $user = User::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Renamed',
            'email' => $user->email,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => $customer->id]);
    }

    public function test_invalid_or_soft_deleted_customer_cannot_be_assigned(): void
    {
        $admin = $this->admin();
        $customer = Cliente::factory()->create();
        $customer->delete();
        $user = User::factory()->create();

        foreach ([$customer->id, 999999, 'not-an-id'] as $invalidId) {
            $this->actingAs($admin)->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'customer_id' => $invalidId,
            ])->assertSessionHasErrors('customer_id');
            $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => null]);
        }
    }

    public function test_invalid_customer_is_rejected_on_creation_without_creating_user(): void
    {
        $admin = $this->admin();
        $customer = Cliente::factory()->create();
        $customer->delete();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Invalid link',
            'email' => 'invalid@example.test',
            'password' => 'password123',
            'customer_id' => $customer->id,
        ])->assertSessionHasErrors('customer_id');

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
    }

    public function test_non_admin_cannot_assign_customer_through_user_routes(): void
    {
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'Unauthorized',
            'email' => 'unauthorized@example.test',
            'password' => 'password123',
            'customer_id' => $customer->id,
        ])->assertForbidden();
        $this->actingAs($user)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'customer_id' => $customer->id,
        ])->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => null]);
    }

    public function test_admin_forms_show_customer_choices_and_current_assignment(): void
    {
        $admin = $this->admin();
        $customer = Cliente::factory()->create(['name' => 'Selected Customer']);
        $user = User::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($admin)->get(route('admin.users.create'))
            ->assertSee('name="customer_id"', false)
            ->assertSee('Selected Customer');
        $this->actingAs($admin)->get(route('admin.users.edit', $user))
            ->assertSee('name="customer_id"', false)
            ->assertSee('value="'.$customer->id.'" selected', false);
    }

    public function test_hard_deleting_customer_clears_link_without_deleting_user(): void
    {
        $customer = Cliente::factory()->create();
        $user = User::factory()->create(['customer_id' => $customer->id]);

        $customer->forceDelete();

        $this->assertModelExists($user);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'customer_id' => null]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'administrator']);
        $permission = Permission::firstOrCreate(['name' => 'manage-users'], ['description' => 'manage-users']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);

        return $admin;
    }
}
