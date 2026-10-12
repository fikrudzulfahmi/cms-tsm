<?php

namespace App\Http\Resources\Admin;

use App\Enums\Role;
use App\Http\Resources\Concerns\FormatsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pengguna panel (`apiResource /admin/users`, khusus Super Admin).
 *
 * Password, token 2FA, dan `remember_token` tidak pernah disertakan
 * (Lihat juga `$hidden` pada model User).
 */
class UserResource extends JsonResource
{
    use FormatsMedia;

    public function toArray(Request $request): array
    {
        $role = $this->role;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $role instanceof Role ? $role->value : $role,
            'role_label' => $role instanceof Role ? $role->label() : null,
            'avatar' => $this->storageUrl($this->avatar),
            'is_active' => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
