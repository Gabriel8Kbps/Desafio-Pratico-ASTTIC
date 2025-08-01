<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;

class AuthController extends Controller
{
    /**
     * Registrar um novo usuário.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'senha' => 'required|string|min:8|confirmed',
            'tipo' => 'required|in:submissor,avaliador,decisor',
        ]);

        $usuario = Usuario::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'senha' => Hash::make($request->senha),
            'tipo' => $request->tipo,
        ]);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $usuario,
        ], 201);
    }

    /**
     * Logar um usuário e emitir um token de acesso.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'senha' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->senha, $usuario->senha)) {
            return response()->json([
                'message' => 'Credenciais inválidas.'
            ], 401);
        }


        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $usuario,
        ]);
    }

    /**
     * Fazer logout do usuário (revogar o token atual).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoga o token atual que está sendo usado para a requisição
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.'
        ]);
    }

    /**
     * Obter informações do usuário autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}