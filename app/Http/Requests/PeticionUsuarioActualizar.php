<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class PeticionUsuarioActualizar extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$this->route('user')->id],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $validator->errors()->isEmpty()) {
                return;
            }

            /** @var User $user */
            $user = $this->route('user');

            if ($user->id !== $this->user()->id) {
                return;
            }

            $submittedRoleIds = $this->input('roles', []);

            $wouldLoseManageUsers = ! DB::table('role_user')
                ->join('permission_role', 'role_user.role_id', '=', 'permission_role.role_id')
                ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
                ->where('role_user.user_id', $user->id)
                ->where('permissions.name', 'manage-users')
                ->whereIn('role_user.role_id', $submittedRoleIds)
                ->exists();

            if ($wouldLoseManageUsers) {
                $validator->errors()->add(
                    'roles',
                    'No podés quitarte tu propio acceso de administración.'
                );
            }
        });
    }
}
