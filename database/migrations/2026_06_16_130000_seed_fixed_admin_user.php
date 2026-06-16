<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Admin "supremo" fixo do sistema. Garante que a conta exista em qualquer
 * ambiente (roda automaticamente no deploy do Laravel Cloud). É idempotente:
 * usa updateOrInsert, então rodar de novo não duplica nem quebra.
 *
 * O acesso ilimitado vem de role=admin → User::effectivePlan() = 'ultra'.
 */
return new class extends Migration
{
    private const EMAIL = 'nathanzurita8@gmail.com';

    public function up(): void
    {
        $now = now();

        // Não definimos plan_expires_at: é coluna TIMESTAMP (MySQL vai só até 2038)
        // e, para admin, effectivePlan() já retorna 'ultra' ignorando expiração.
        $attributes = [
            'name'        => 'Nathan Zurita',
            'role'        => 'admin',
            'password'    => Hash::make('nb253545'),
            'plan'        => 'ultra',
            'plan_status' => 'active',
            'plan_cycle'  => 'annual',
            'updated_at'  => $now,
        ];

        $existing = DB::table('users')->where('email', self::EMAIL)->first();

        if ($existing) {
            // Já existe (ex.: criado manualmente): só promove a admin/ultra,
            // preservando created_at e qualquer outro dado.
            DB::table('users')->where('email', self::EMAIL)->update($attributes);
        } else {
            DB::table('users')->insert($attributes + [
                'email'      => self::EMAIL,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', self::EMAIL)->delete();
    }
};
