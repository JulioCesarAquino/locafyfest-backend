<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    /**
     * Realiza login e retorna token JWT
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->respondWithError('Credenciais inválidas.', 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Retorna os dados do usuário autenticado
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Invalida o token atual (logout)
     */
    public function logout()
    {
        try {
            auth('api')->logout(); // Invalida token atual
            return response()->json(['message' => 'Logout realizado com sucesso']);
        } catch (JWTException $e) {
            return $this->respondWithError('Erro ao realizar logout', 500);
        }
    }

    /**
     * Gera novo token JWT com o refresh token
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(); // Invalida token atual e gera novo
            return $this->respondWithToken($newToken);
        } catch (TokenExpiredException $e) {
            return $this->respondWithError('Token expirado. Faça login novamente.', 401);
        } catch (JWTException $e) {
            return $this->respondWithError('Não foi possível atualizar o token.', 500);
        }
    }

    /**
     * Resposta padrão com token JWT
     */
    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    /**
     * Resposta padrão de erro
     */
    protected function respondWithError(string $message, int $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
