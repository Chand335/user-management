<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAuditLogRequest;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\UserResource;
use App\Mail\ResetPasswordMail;
use App\Mail\WelcomeMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UserApiController extends Controller
{

  /**
   * Display a listing of the resource.
   */
  public function index(IndexUserRequest $request)
  {

    $user = $request->user();
    $key = 'user_list:' . $user->id;

    if (RateLimiter::tooManyAttempts($key, 20)) {
      return response()->json([
        'success' => false,
        'message' => 'Too many attempts. Please try again later.',
      ], 429);
    }

    RateLimiter::hit($key, 60);
    $query = User::query();

    if ($request->search) {
      $query->where(
        fn($q) =>
        $q->where('name', 'like', "%{$request->search}%")
          ->orWhere('email', 'like', "%{$request->search}%")
      );
    }

    $allowedSorts = ['name', 'email', 'created_at'];

    $sortBy = in_array($request->sort_by, $allowedSorts)
      ? $request->sort_by
      : 'created_at';

    $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

    $query->orderBy($sortBy, $sortDir);

    $users = $query->with('auditLogs','roles')->paginate($request->per_page ?? 10);
    return response()->json([
      'success' => true,
      'message' => 'Users fetched successfully',
      'data' => UserResource::collection($users->items()),
      'meta' => [
        'current_page' => $users->currentPage(),
        'last_page' => $users->lastPage(),
        'per_page' => $users->perPage(),
        'total' => $users->total(),
      ],
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(StoreUserRequest $request)
  {
    $validated = $request->validated();
    try {
      DB::beginTransaction();
      $user = User::create([
        'uuid' => Str::uuid(),
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
      ]);

      $user->syncRoles($request->roles);

      Mail::to($request->email)
        ->later(now()->addSeconds(30), new WelcomeMail($user));
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
    $validated = $request->validated();

    try {
      DB::beginTransaction();

      $user->update([
        'name' => $request->name ?? $user->name,
        'email' => $request->email ?? $user->email,
        'password' => $request->password ? Hash::make($request->password) : $user->password,
      ]);

      $user->syncRoles($request->roles);

      DB::commit();
      return response()->json([
        'success' => true,
        'message' => 'User updated successfully',
        'data' => $user,
      ], status: 200);
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
    abort_if(Gate::denies('manage_users'), 403, 'Unauthorized');
    // prevent deleting self
    if (auth()->id() === $user->id) {
      return response()->json([
        'success' => false,
        'message' => 'You cannot delete yourself',
      ], 403);
    }
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
    if (RateLimiter::tooManyAttempts('resetPassword:' . $user->id, $perMinute = 2)) {
      return response()->json([
        'success' => false,
        'message' => 'Too many attempts. Please try again later.',
      ], 429);
    }
    RateLimiter::hit('resetPassword:' . $user->id, 60);

    $token = Password::createToken($user);

    Mail::to($request->email)
      ->later(now()->addSeconds(30), new ResetPasswordMail($data = [
        'name' => $user->name,
        'email' => $user->email,
        'token' => $token,
      ]));
    AuditLogService::log([
      'action' => 'reset_password_requested',
      'target_user_id' => $user->id,
      'actor_user_id' => $user->id
    ]);
    return response()->json([
      'success' => true,
      'message' => 'Please check your email for the Rest Password.',
    ], 200);
  }

  // auditLogs
  public function auditLogs(IndexAuditLogRequest $request)
  {

    $query = AuditLog::query();

    if ($request->search) {
      $query->where(
        fn($q) =>
        $q->where('action', 'like', "%{$request->search}%")
          ->orWhere('ip_address', 'like', "%{$request->search}%")
      );
    }

    $allowedSorts = ['ip_address', 'user_agent', 'created_at'];

    $sortBy = in_array($request->sort_by, $allowedSorts)
      ? $request->sort_by
      : 'created_at';

    $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';

    $query->orderBy($sortBy, $sortDir);

    $logs = $query->with('actor', 'target')->paginate($request->per_page ?? 10);

    return response()->json([
      'success' => true,
      'message' => 'Audit logs fetched successfully',
      'data' => AuditLogResource::collection($logs->items()),
      'meta' => [
        'current_page' => $logs->currentPage(),
        'last_page' => $logs->lastPage(),
        'per_page' => $logs->perPage(),
        'total' => $logs->total(),
      ],
    ], 200);
  }
}
