<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Post;
use App\Models\Attachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'caption' => 'required|string',
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,gif'
        ]);

        $post = Post::create([
            'user_id' => auth()->id(),
            'caption' => $request->caption,
        ]);

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('posts', 'public');
            Attachment::create([
                'post_id' => $post->id,
                'storage_path' => $path
            ]);
        }

        return response()->json(['message' => 'Create post success'], 201);
    }

    public function destroy($id)
    {
        $post = Post::with('attachments')->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden access'], 403);
        }

        foreach ($post->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->storage_path);
        }

        $post->delete();

        return response()->noContent(); // 204
    }

    public function index(Request $request)
    {
        $size = max((int) $request->query('size', 10), 1);

        $posts = Post::with(['user', 'attachments', 'likes'])
            ->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhereIn('user_id', auth()->user()->followings()->pluck('following_id'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($size);

        return response()->json($posts);
    }

    public function like($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $message = "";

        $liked = null;

        if ($post->likes()->where('user_id', auth()->id())->count()) {
            $post->likes()->where('user_id', auth()->id())->delete();
            $message = "success to remove like from the post";
        } else {
            $liked = $post->likes()->create(['user_id' => auth()->id()]);
            $message = "success to like the post";
        }

        return response()->json(['message' => $message, 'data' => $liked]);
    }
}
