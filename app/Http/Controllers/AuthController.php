<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $token = $user->createToken('api')->plainTextToken;

            SendWelcomeEmail::dispatch($user)->afterCommit();

            return response()->json([
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token, 
                    'token_type' => 'Bearer'
                ]
            ], 201);
        });
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.'
            ], 401);
        }

        return response()->json([
            'data' => [
                'user' => new UserResource($user), 
                'token' => $user->createToken('api')->plainTextToken,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
