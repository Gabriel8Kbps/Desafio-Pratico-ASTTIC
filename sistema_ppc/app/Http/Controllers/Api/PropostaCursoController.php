<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\PropostaCurso;
use App\Models\Disciplina;
use App\Models\StatusProposta;
use Illuminate\Support\Facades\DB;

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
            'disciplinas' => 'required|array|min:1', // Deve haver pelo menos uma disciplina
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

    /**
     * Lista propostas de curso com base no tipo de usuário.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $propostas = PropostaCurso::query();

        switch ($user->tipo) {
            case 'submissor':
                // Um submissor só pode ver suas próprias propostas
                $propostas->where('id_autor', $user->id);
                break;
            case 'avaliador':
                // Um avaliador pode ver propostas em 'submetida' ou 'em_avaliacao' ou as que foram designadas a ele
                $propostas->whereHas('historicoStatus', function ($query) {
                    $query->whereIn('status', ['submetida', 'em_avaliacao']);
                })->orWhere('id_avaliador', $user->id); // Ou aquelas que ele foi designado
                break;
            case 'decisor':
                // Um decisor pode ver propostas que estão 'em_aprovacao'
                $propostas->whereHas('historicoStatus', function ($query) {
                    $query->where('status', 'em_aprovacao');
                });
                break;
            default:
                // Caso para tipos de usuário não definidos, não retornar nada
                return response()->json([], 200);
        }

        // Carrega as disciplinas e o histórico de status para cada proposta
        $propostas = $propostas->with('disciplinas', 'historicoStatus')->get();

        return response()->json([
            'message' => 'Propostas listadas com sucesso.',
            'propostas' => $propostas
        ]);
    }

    /**
     * Exibe uma proposta de curso específica.
     *
     * @param  \App\Models\PropostaCurso  $proposta
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PropostaCurso $proposta)
    {
        $user = Auth::user();

        // Autorização para visualizar (mesma lógica do index, mas para uma única proposta)
        $canView = false;
        switch ($user->tipo) {
            case 'submissor':
                $canView = ($proposta->id_autor === $user->id);
                break;
            case 'avaliador':
                // Verifica se a proposta está em um status visível para avaliador OU se ele é o avaliador designado
                $canView = $proposta->historicoStatus()->whereIn('status', ['submetida', 'em_avaliacao'])->exists() || $proposta->id_avaliador === $user->id;
                break;
            case 'decisor':
                $canView = $proposta->historicoStatus()->where('status', 'em_aprovacao')->exists();
                break;
        }

        if (!$canView) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar esta proposta.'
            ], 403);
        }

        // Carrega os relacionamentos para a resposta
        $proposta->load('disciplinas', 'historicoStatus', 'autor', 'avaliador', 'decisorFinal');

        return response()->json([
            'message' => 'Detalhes da proposta.',
            'proposta' => $proposta
        ]);
    }

        /**
     * Avalia uma proposta de curso, adicionando comentários e mudando o status.
     * Apenas avaliadores podem usar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PropostaCurso  $proposta
     * @return \Illuminate\Http\JsonResponse
     */
    public function avaliar(Request $request, PropostaCurso $proposta)
    {
        $user = Auth::user();

        // 1. Autorização: Apenas 'avaliador' pode avaliar
        if ($user->tipo !== 'avaliador') {
            return response()->json([
                'message' => 'Você não tem permissão para avaliar propostas de curso.'
            ], 403);
        }

        // 2. Validação: Comentário e novo status são obrigatórios
        $validator = Validator::make($request->all(), [
            'comentario' => 'required|string',
            'status_novo' => 'required|in:ajustes_requeridos,em_aprovacao', // Status permitidos para avaliação
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Verifica o status atual da proposta para permitir avaliação
        // Um avaliador só pode interagir com propostas 'submetida' ou 'em_avaliacao'
        $currentStatus = $proposta->historicoStatus()->latest('data_status')->first()->status;
        if (!in_array($currentStatus, ['submetida', 'em_avaliacao'])) {
            return response()->json([
                'message' => "Esta proposta não pode ser avaliada neste momento (Status atual: {$currentStatus})."
            ], 400); // Bad Request
        }

        DB::beginTransaction();
        try {
            // Atualiza o comentário do avaliador e atribui o avaliador
            $proposta->comentario_avaliador = $request->comentario;
            $proposta->id_avaliador = $user->id; // Atribui o avaliador atual
            $proposta->save();

            // Registra o novo status no histórico
            StatusProposta::create([
                'id_proposta' => $proposta->id,
                'status' => $request->status_novo,
                'data_status' => now(),
                'observacao' => "Avaliação: {$request->comentario}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Proposta avaliada com sucesso!',
                'proposta' => $proposta->load('historicoStatus', 'avaliador')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao avaliar a proposta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decide (aprova ou reprova) uma proposta de curso.
     * Apenas decisores podem usar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PropostaCurso  $proposta
     * @return \Illuminate\Http\JsonResponse
     */
    public function decidir(Request $request, PropostaCurso $proposta)
    {
        $user = Auth::user();

        // 1. Autorização: Apenas 'decisor' pode decidir
        if ($user->tipo !== 'decisor') {
            return response()->json([
                'message' => 'Você não tem permissão para decidir sobre propostas de curso.'
            ], 403);
        }

        // 2. Validação: Comentário e status de decisão são obrigatórios
        $validator = Validator::make($request->all(), [
            'comentario' => 'required|string',
            'status_final' => 'required|in:aprovada,rejeitada', // Status permitidos para decisão final
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Verifica o status atual da proposta para permitir decisão
        // Um decisor só pode decidir sobre propostas 'em_aprovacao'
        $currentStatus = $proposta->historicoStatus()->latest('data_status')->first()->status;
        if ($currentStatus !== 'em_aprovacao') {
            return response()->json([
                'message' => "Esta proposta não pode ser decidida neste momento (Status atual: {$currentStatus})."
            ], 400); // Bad Request
        }

        DB::beginTransaction();
        try {
            // Atualiza o comentário do decisor e atribui o decisor final
            $proposta->comentario_decisor = $request->comentario;
            $proposta->id_decisor_final = $user->id; // Atribui o decisor final
            $proposta->save();

            // Registra o novo status final no histórico
            StatusProposta::create([
                'id_proposta' => $proposta->id,
                'status' => $request->status_final,
                'data_status' => now(),
                'observacao' => "Decisão final: {$request->comentario}",
            ]);

            DB::commit();

            return response()->json([
                'message' => "Proposta {$request->status_final} com sucesso!",
                'proposta' => $proposta->load('historicoStatus', 'decisorFinal')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao tomar a decisão sobre a proposta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}