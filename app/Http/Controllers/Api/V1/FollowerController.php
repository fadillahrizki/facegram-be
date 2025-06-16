<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;

class FollowerController extends Controller
{
    public function accept($username)
    {
        $follower = User::where('username', $username)->first();
        if (!$follower) return response()->json(['message' => 'User not found'], 404);

        $follow = Follow::where([
            ['follower_id', $follower->id],
            ['following_id', auth()->id()],
            ['is_accepted', false]
        ])->first();

        if (!$follow) {
            return response()->json(['message' => 'The user is not following you'], 422);
        }

        $follow->update(['is_accepted' => true]);
        return response()->json(['message' => 'Follow request accepted']);
    }

    public function reject($username)
    {
        $follower = User::where('username', $username)->first();
        if (!$follower) return response()->json(['message' => 'User not found'], 404);

        $follow = Follow::where([
            ['follower_id', $follower->id],
            ['following_id', auth()->id()],
            ['is_accepted', false]
        ])->first();

        if (!$follow) {
            return response()->json(['message' => 'The user is not following you'], 422);
        }

        $follow->update(['is_rejected' => true]);
        return response()->json(['message' => 'Follow request rejected']);
    }

    public function followers()
    {

        $followers = auth()->user()->followers()->with('follower')->get()->map(function ($f) {
            $u = $f->follower;
            return [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'username' => $u->username,
                'bio' => $u->bio,
                'is_private' => $u->is_private,
                'created_at' => $u->created_at,
                'is_requested' => !$f->is_accepted
            ];
        });

        return response()->json($followers);
    }

    public function pendingFollowers()
    {
        $pendingFollowers = auth()->user()->pendingFollowers()->with('follower')->get()->map(function ($f) {
            $u = $f->follower;
            return [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'username' => $u->username,
                'bio' => $u->bio,
                'is_private' => $u->is_private,
                'created_at' => $u->created_at,
                'is_requested' => !$f->is_accepted
            ];
        });

        return response()->json($pendingFollowers);
    }

    public function rejectedFollowers()
    {
        $rejectedFollowers = auth()->user()->rejectedFollowers()->with('follower')->get()->map(function ($f) {
            $u = $f->follower;
            return [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'username' => $u->username,
                'bio' => $u->bio,
                'is_private' => $u->is_private,
                'created_at' => $u->created_at,
                'is_requested' => $f->is_accepted,
                'is_rejected' => $f->is_rejected,
            ];
        });

        return response()->json($rejectedFollowers);
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications();

        return response()->json($notifications);
    }
}
