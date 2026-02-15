<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
  public function login(LoginRequest $request)
  {
    //
    $validated = $request->validated();
    if (!Auth::attempt($validated)) {
      throw ValidationException::withMessages([
        'email' => ['Invalid credentials.'],
      ]);
    }

    $user = $request->user();

    if (!$user->is_active) {
      return response()->json([
        'success' => false,
        'message' => 'Account is inactive.',
      ], 403);
    }

    $token = $user->createToken('api_token')->plainTextToken;

    return response()->json([
      'success' => true,
      'message' => 'Login successful.',
      'data' => [
        'user' => [
          'id'    => $user->id,
          'uuid'  => $user->uuid,
          'name'  => $user->name,
          'email' => $user->email,
        ],
        'token' => $token,
      ],
    ]);
  }

  // logout
  public function logout(Request $request)
  {
    $request->user()->tokens()->delete();
    return response()->json([
      'success' => true,
      'message' => 'Logout successful',
    ]);
  }
}
