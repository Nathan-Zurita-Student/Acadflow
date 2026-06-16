<?php

use App\Models\ProjectColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('key');                 // chave estável usada no task.status
            $table->string('label');               // nome exibido (editável)
            $table->string('color')->default('text-slate-400');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false); // colunas padrão não podem ser excluídas
            $table->timestamps();

            $table->unique(['project_id', 'key']);
        });

        // Semeia as 5 colunas padrão para todos os projetos que já existem,
        // para que os Kanbans atuais continuem funcionando.
        $now = now();
        DB::table('projects')->orderBy('id')->pluck('id')->each(function ($projectId) use ($now) {
            $rows = [];
            foreach (ProjectColumn::DEFAULTS as $pos => $col) {
                $rows[] = [
                    'project_id' => $projectId,
                    'key'        => $col['key'],
                    'label'      => $col['label'],
                    'color'      => $col['color'],
                    'position'   => $pos,
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('project_columns')->insert($rows);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_columns');
    }
};
