<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload público do usuário autenticado, consumido pela SPA.
 * Mantém o mesmo formato usado historicamente + o estado de verificação.
 *
 * @mixin \App\Models\User
 */
class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'display_name'    => $this->display_name,
            'email'           => $this->email,
            'role'            => $this->role,
            'avatar'          => $this->avatar,
            'plan'            => $this->effectivePlan(),
            'plan_status'     => $this->plan_status,
            'plan_expires_at' => $this->plan_expires_at,
            'email_verified'  => ! is_null($this->email_verified_at),
            'created_at'      => $this->created_at,
        ];
    }
}
