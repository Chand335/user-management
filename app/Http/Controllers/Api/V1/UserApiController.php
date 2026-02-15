<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\ResetPasswordMail;
use App\Mail\WelcomeMail;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UserApiController extends Controller
{

  public function __construct()
  {
    // Auth::guard('sanctum');
  }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      $query = User::query();

if ($request->search) {
    $query->where(fn($q) =>
        $q->where('name', 'like', "%{$request->search}%")
          ->orWhere('email', 'like', "%{$request->search}%")
    );
}

$query->orderBy(
    $request->sort_by ?? 'created_at',
    $request->sort_dir ?? 'desc'
);

$users = $query->with('auditLogs')->paginate($request->per_page ?? 10);
        return response()->json([
        'success' => true,
        'message' => 'Users fetched successfully',
        'data' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $request->validated();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
        try {
          DB::beginTransaction();
            $user = User::create([
              'uuid' => Str::uuid(),
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $user->syncRoles($request->roles);

             Mail::to($request->email)
            ->later(now()->addSeconds(30),new WelcomeMail($user));
          DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
          DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'User creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
       
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $request->validated();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
         try {
          DB::beginTransaction();

          $user->update([
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
                'password' => $request->password ? bcrypt($request->password) : $user->password,
            ]);

            $user->syncRoles($request->roles);

          DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
          DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'User updation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
      // auth()->user()->can('delete users');
      // prevent deleting self
      // if (auth()->id() === $user->id) {
      //   return response()->json([
      //       'success' => false,
      //       'message' => 'You cannot delete yourself',
      //   ], 403);
      // }
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }

    // resetPassword
    public function resetPassword(Request $request)
    {
     
      $request->validate([
        'email' => 'required|email|exists:users,email',
      ]);

      $user = User::where('email', $request->email)->first();
      if (RateLimiter::tooManyAttempts('resetPassword:'.$user->id, $perMinute = 2)) {
          return response()->json([
              'success' => false,
              'message' => 'Too many attempts. Please try again later.',
          ], 429);
      }
      RateLimiter::increment('resetPassword:'.$user->id);

      $token = $user->createToken('auth_token')->plainTextToken;

       Mail::to($request->email)
            ->later(now()->addSeconds(30),new ResetPasswordMail($data = [
              'name' => $user->name,
              'email' => $user->email,
              'token'=> $token,
            ]));
      AuditLogService::log([
          'action' => 'reset_password_requested',
          'target_user_id' => $user->id,
      ]);
      return response()->json([
        'success' => true,
        'message' => 'Please check your email for the Rest Password.',
      ], 200);
    }
}
