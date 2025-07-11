<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProfilePicture;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', auth()->id())->with('profilePicture')
            ->get();

        return response()->json($users);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|min:1',
            'bio' => 'required|string|min:1|max:100',
            'username' => [
                'required',
                'min:3',
                'regex:/^[a-zA-Z0-9._]+$/',
                Rule::unique('users')->ignore(auth()->user()->username, 'username')
            ],
            'is_private' => 'boolean'
        ]);

        User::where('id', auth()->id())->update([
            'full_name' => $validated['full_name'],
            'bio' => $validated['bio'],
            'username' => $validated['username'],
            'is_private' => $validated['is_private'] ?? false
        ]);

        return response()->json([
            'message' => 'Update success',
            'user' => User::find(auth()->id())
        ], 200);
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|file|mimes:jpg,jpeg,png,webp,gif',
        ]);

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $updated = ProfilePicture::updateOrCreate(['user_id' => auth()->id()], ['storage_path' => $path]);

        return response()->json([
            'message' => 'success update profile picture',
            'data' => $updated
        ], 200);
    }

    public function show($username)
    {
        $user = User::where('username', $username)->with('profilePicture')->first();
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
            $posts = $user->posts()->with('attachments', 'user.profilePicture', 'likes', 'comments.user.profilePicture')->latest()->get()->map(function ($post) {
                return [
                    'id' => $post->id,
                    'user' => $post->user,
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
            'profile_picture' => $user->profilePicture,
            'posts' => $posts
        ]);
    }
}
