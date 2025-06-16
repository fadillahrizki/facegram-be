<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Follow;

class FollowController extends Controller
{
    public function follow($username)
    {
        if (auth()->user()->username === $username) {
            return response()->json(['message' => 'You are not allowed to follow yourself'], 422);
        }

        $target = User::where('username', $username)->first();
        if (!$target) return response()->json(['message' => 'User not found'], 404);

        $existing = Follow::where([
            ['follower_id', auth()->id()],
            ['following_id', $target->id]
        ])->first();

        if ($existing) {
            return response()->json([
                'message' => 'You are already followed',
                'status' => $existing->is_accepted ? 'following' : 'requested'
            ], 422);
        }

        $follow = Follow::create([
            'follower_id' => auth()->id(),
            'following_id' => $target->id,
            'is_accepted' => !$target->is_private
        ]);

        return response()->json([
            'message' => 'Follow success',
            'status' => $follow->is_accepted ? 'following' : 'requested'
        ]);
    }

    public function unfollow($username)
    {
        $target = User::where('username', $username)->first();
        if (!$target) return response()->json(['message' => 'User not found'], 404);

        $follow = Follow::where([
            ['follower_id', auth()->id()],
            ['following_id', $target->id]
        ])->first();

        if (!$follow) return response()->json(['message' => 'You are not following the user'], 422);

        $follow->delete();
        return response()->noContent();
    }

    public function following()
    {
        $followings = auth()->user()->followings()->with('following')->get()->map(function ($f) {
            $user = $f->following;
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'is_private' => $user->is_private,
                'created_at' => $user->created_at,
                'is_requested' => !$f->is_accepted
            ];
        });

        return response()->json($followings);
    }
}
