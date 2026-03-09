<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ManagerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autenticado',
                'error' => 'Unauthenticated'
            ], 401);
        }

        if (!($request->user()->isAdmin() || $request->user()->isManager())) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores e gerentes podem acessar este recurso.',
                'error' => 'Forbidden'
            ], 403);
        }

        return $next($request);
    }
}

