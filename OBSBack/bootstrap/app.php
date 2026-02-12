<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            // Si es un error de conexión a la base de datos
            if (str_contains(strtolower($e->getMessage()), 'connection') || 
                str_contains(strtolower($e->getMessage()), 'sqlstate[hy000]') ||
                str_contains(strtolower($e->getMessage()), 'no such file') ||
                str_contains(strtolower($e->getMessage()), 'access denied')) {
                
                return response()->json([
                    'message' => 'En este momento no podemos conectar con la base de datos. Por favor, intenta más tarde.',
                    'error' => 'connection_error',
                    'timestamp' => now()->toISOString()
                ], 503);
            }
            
            // Para otros errores de consulta, mostrar mensaje genérico
            return response()->json([
                'message' => 'Ocurrió un error al procesar tu solicitud. Por favor, intenta más tarde.',
                'error' => 'database_error',
                'timestamp' => now()->toISOString()
            ], 500);
        });
    })->create();
