<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Admin inicial do sistema, criado a partir de credenciais do AMBIENTE
 * (ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME). Nenhuma credencial é versionada.
 * Sem ADMIN_EMAIL/ADMIN_PASSWORD definidos, a migration é um no-op.
 *
 * Idempotente (updateOrInsert): promove uma conta existente a admin/ultra.
 * O acesso ilimitado vem de role=admin → User::effectivePlan() = 'ultra'.
 */
return new class extends Migration
{
    public function up(): void
    {
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            return;
        }

        $now = now();

        $attributes = [
            'name'              => env('ADMIN_NAME', 'Administrador'),
            'role'              => 'admin',
            'password'          => Hash::make($password),
            'plan'              => 'ultra',
            'plan_status'       => 'active',
            'plan_cycle'        => 'annual',
            'email_verified_at' => $now,
            'updated_at'        => $now,
        ];

        $existing = DB::table('users')->where('email', $email)->first();

        if ($existing) {
            DB::table('users')->where('email', $email)->update($attributes);
        } else {
            DB::table('users')->insert($attributes + [
                'email'      => $email,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if ($email = env('ADMIN_EMAIL')) {
            DB::table('users')->where('email', $email)->delete();
        }
    }
};
