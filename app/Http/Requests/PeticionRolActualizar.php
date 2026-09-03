<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class PeticionRolActualizar extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$this->route('role')->id],
            'description' => ['nullable', 'string'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $validator->errors()->isEmpty()) {
                return;
            }

            /** @var Role $role */
            $role = $this->route('role');

            if ($this->user()->roles()->where('roles.id', $role->id)->doesntExist()) {
                return;
            }

            $submittedPermissionIds = $this->input('permissions', []);

            $stillHasManageUsers = DB::table('permission_role')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('permission_role.role_id', $role->id)
                ->where('permissions.name', 'manage-users')
                ->whereIn('permission_role.permission_id', $submittedPermissionIds)
                ->exists();

            if ($stillHasManageUsers) {
                return;
            }

            $hasAnotherManageUsersSource = DB::table('role_user')
                ->join('permission_role', 'role_user.role_id', '=', 'permission_role.role_id')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('role_user.user_id', $this->user()->id)
                ->where('role_user.role_id', '!=', $role->id)
                ->where('permissions.name', 'manage-users')
                ->exists();

            if (! $hasAnotherManageUsersSource) {
                $validator->errors()->add(
                    'permissions',
                    'No podés quitarle el permiso de gestión al único rol que te da acceso al panel.'
                );
            }
        });
    }
}
