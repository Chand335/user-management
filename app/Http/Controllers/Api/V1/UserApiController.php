<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserApiController extends Controller
{
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

$users = $query->paginate($request->per_page ?? 10);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
