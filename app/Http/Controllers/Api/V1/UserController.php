<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', auth()->id())
            ->get();

        return response()->json($users);
    }


    public function show($username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $isYourAccount = auth()->id() === $user->id;

        // Cek status follow
        $follow = \App\Models\Follow::where('follower_id', auth()->id())
            ->where('following_id', $user->id)
            ->first();

        $followingStatus = 'not-following';
        if ($follow) {
            $followingStatus = $follow->is_accepted ? 'following' : ($follow->is_rejected ? 'rejected' : 'requested');
        }

        // Hitung jumlah
        $postsCount = $user->posts()->count();
        $followersCount = $user->followers()->count();
        $followingCount = $user->followings()->count();

        // Tentukan apakah boleh tampilkan posts
        $canViewPosts = $isYourAccount || !$user->is_private || $followingStatus === 'following';

        $posts = [];
        if ($canViewPosts) {
            $posts = $user->posts()->with('attachments', 'user', 'likes', 'comments.user')->latest()->get()->map(function ($post) {
                return [
                    'id' => $post->id,
                    'user' => [
                        'id' => $post->user->id,
                        'full_name' => $post->user->full_name,
                        'username' => $post->user->username,
                    ],
                    'likes' => $post->likes,
                    'comments' => $post->comments,
                    'caption' => $post->caption,
                    'created_at' => $post->created_at,
                    'deleted_at' => $post->deleted_at,
                    'attachments' => $post->attachments->map(function ($a) {
                        return [
                            'id' => $a->id,
                            'storage_path' => $a->storage_path
                        ];
                    })
                ];
            });
        }

        return response()->json([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'bio' => $user->bio,
            'is_private' => $user->is_private,
            'created_at' => $user->created_at,
            'is_your_account' => $isYourAccount,
            'following_status' => $followingStatus,
            'posts_count' => $postsCount,
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
            'posts' => $posts
        ]);
    }
}
