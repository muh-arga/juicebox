<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $perPage = $request->input('per_page', 15);

        return UserResource::collection(
            User::orderBy('id')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }
}
