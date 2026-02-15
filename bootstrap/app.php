<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
      'role' => RoleMiddleware::class,
      'permission' => PermissionMiddleware::class,
      'role_or_permission' => RoleOrPermissionMiddleware::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (AuthenticationException $e, $request) {
      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized. Invalid or missing token.',
          'error_code' => 401,
        ], 401);
      }
    });
    $exceptions->render(function (NotFoundHttpException $e, $request) {

      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'Resource not found.',
          'error_code' => 404,
        ], 404);
      }
    });
    $exceptions->render(function (ValidationException $e, $request) {
      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'Validation failed',
          'errors'  => $e->errors(),
          'error_code' => 422
        ], 422);
      }
    });
    $exceptions->render(function (AuthorizationException $e, $request) {
      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'You are not authorized to perform this action.',
        ], 403);
      }
    });
    $exceptions->render(function (UnauthorizedException $e, $request) {
      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'You are not authorized to perform this action.',
          'error_code' => 403
        ], 403);
      }
    });
    $exceptions->render(function (ThrottleRequestsException $e, $request) {
      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'Too many requests. Please try again later.',
          'error_code' => 429
        ], 429);
      }
    });
  })->create();
