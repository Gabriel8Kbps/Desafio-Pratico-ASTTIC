<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // pega o usuário autenticado
use Illuminate\Support\Facades\Validator; // valid manual
use App\Models\PropostaCurso;
use App\Models\Disciplina;
use App\Models\StatusProposta;
use Illuminate\Support\Facades\DB; // transações de banco de dados

class PropostaCursoController extends Controller
{
    /**
     * Armazena uma nova proposta de curso no banco de dados.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 1. Autorização: Apenas 'submissor' pode criar propostas
        if (Auth::user()->tipo !== 'submissor') {
            return response()->json([
                'message' => 'Você não tem permissão para submeter propostas de curso.'
            ], 403);
        }

        // 2. Validação dos dados da proposta e das disciplinas
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'carga_horaria_total' => 'required|integer|min:1',
            'quantidade_semestres' => 'required|integer|min:1',
            'justificativa' => 'required|string',
            'impacto_social' => 'required|string',
            'disciplinas' => 'required|array|min:1', //pelo menos uma disciplina
            'disciplinas.*.nome' => 'required|string|max:255',
            'disciplinas.*.carga_horaria' => 'required|integer|min:1',
            'disciplinas.*.semestre' => 'required|integer|min:1',
        ], [
            'disciplinas.*.nome.required' => 'O nome da disciplina é obrigatório.',
            'disciplinas.*.carga_horaria.required' => 'A carga horária da disciplina é obrigatória.',
            'disciplinas.*.semestre.required' => 'O semestre da disciplina é obrigatório.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Salvar a proposta e suas disciplinas dentro de uma transação
        // Caso algo de errado, o DB não sera salvo
        DB::beginTransaction();
        try {
            $proposta = PropostaCurso::create([
                'nome' => $request->nome,
                'carga_horaria_total' => $request->carga_horaria_total,
                'quantidade_semestres' => $request->quantidade_semestres,
                'justificativa' => $request->justificativa,
                'impacto_social' => $request->impacto_social,
                'id_autor' => Auth::id(), // Atribui o ID do usuário autenticado como autor
            ]);

            // Salvar as disciplinas
            foreach ($request->disciplinas as $disciplinaData) {
                $proposta->disciplinas()->create($disciplinaData);
            }

            // Registrar o status inicial da proposta
            StatusProposta::create([
                'id_proposta' => $proposta->id,
                'status' => 'submetida', // Status inicial conforme o requisito
                'data_status' => now(), // Usa a data e hora atuais
                'observacao' => 'Proposta inicial submetida pela unidade acadêmica.',
            ]);

            DB::commit(); // Confirma todas as operações no banco de dados

            return response()->json([
                'message' => 'Proposta de curso submetida com sucesso!',
                'proposta' => $proposta->load('disciplinas', 'historicoStatus') // Carrega relacionamentos para a resposta
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Desfaz todas as operações em caso de erro
            return response()->json([
                'message' => 'Erro ao submeter a proposta de curso.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}