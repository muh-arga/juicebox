<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginationRequest;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(PaginationRequest $request)
    {
        $perPage = $request->input('per_page', 15);

        return PostResource::collection(
            Post::with('user')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function show(Post $post)
    {
        return new PostResource($post->load('user'));
    }

    public function store(PostRequest $request)
    {
        $post = $request->user()->posts()->create($request->safe()->only(['title', 'body']));

        return new PostResource($post->load('user'));
    }

    public function update(PostRequest $request, Post $post)
    {
        Gate::authorize('update', $post);
        $post->update($request->safe()->only(['title', 'body']));

        return new PostResource($post->load('user'));
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return response()->noContent();
    }
}
