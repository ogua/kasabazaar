<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'avatar'      => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'role'        => $this->role,
            'client_id'   => $this->client_id,
            'branch_id'   => $this->branch_id,
            'branches'    => $this->whenLoaded('branches', fn () =>
                $this->branches->map(fn ($b) => [
                    'id'       => $b->id,
                    'name'     => $b->name,
                    'slug'     => $b->slug,
                    'location' => trim(collect([$b->state, $b->country])->filter()->implode(', ')),
                ])->values()
            ),
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'roles'       => $this->getRoleNames()->values(),
            'staff'       => $this->whenLoaded('staff', fn () => $this->staff ? [
                'id'          => $this->staff->id,
                'employee_id' => $this->staff->employee_id,
                'role'        => $this->staff->relationLoaded('role') && $this->staff->role ? [
                    'id'   => $this->staff->role->id,
                    'name' => $this->staff->role->name,
                    'code' => $this->staff->role->code,
                ] : null,
            ] : null),
        ];
    }
}
