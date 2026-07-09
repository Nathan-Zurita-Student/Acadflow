<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_invitations', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('invited_by_user_id');
        });

        DB::table('project_invitations')->whereNull('token')->orderBy('id')
            ->each(fn ($invitation) => DB::table('project_invitations')
                ->where('id', $invitation->id)
                ->update(['token' => Str::random(48)]));
    }

    public function down(): void
    {
        Schema::table('project_invitations', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });
    }
};
