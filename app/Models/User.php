<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'username',
        'password',
        'bio',
        'is_private'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'following_id')->where('is_accepted', true);
    }

    public function followings()
    {
        return $this->hasMany(Follow::class, 'follower_id')->where('is_accepted', true);
    }

    public function pendingFollowers()
    {
        return $this->hasMany(Follow::class, 'following_id')->where('is_accepted', false)->where('is_rejected', false);
    }

    public function rejectedFollowers()
    {
        return $this->hasMany(Follow::class, 'follower_id')->where('is_accepted', false)->where('is_rejected', true);
    }

    public function notifications()
    {
        return [...$this->pendingFollowers()->with('follower')->get()->map(function ($follow) {
            $user = $follow->follower;
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'is_private' => $user->is_private,
                'created_at' => $user->created_at,
                'is_accepted' => $follow->is_accepted,
                'is_rejected' => $follow->is_rejected,
            ];
        }), ...$this->rejectedFollowers()->with('following')->get()->map(function ($follow) {
            $user = $follow->following;
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'is_private' => $user->is_private,
                'created_at' => $user->created_at,
                'is_accepted' => $follow->is_accepted,
                'is_rejected' => $follow->is_rejected,
            ];
        })];
    }
}
