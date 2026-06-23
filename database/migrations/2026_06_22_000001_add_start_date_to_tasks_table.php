<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Data de início da tarefa — usada pelo calendário para desenhar a
            // faixa contínua (start_date → due_date). Nula = tarefa de um dia só.
            $table->date('start_date')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
