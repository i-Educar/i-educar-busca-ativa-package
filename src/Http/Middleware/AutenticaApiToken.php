<?php

namespace iEducar\Packages\BuritiBuscaAtiva\Http\Middleware;

use Closure;
use iEducar\Packages\BuritiBuscaAtiva\Services\ApiTokenService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AutenticaApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken()
            ?: $request->header('X-Busca-Ativa-Token')
            ?: $request->query('token');

        $token = ApiTokenService::validar(is_string($plain) ? $plain : null);
        if ($token === null) {
            return response()->json(['error' => 'Token inválido ou expirado'], 401);
        }

        $request->attributes->set('busca_ativa_token', $token);

        return $next($request);
    }
}
