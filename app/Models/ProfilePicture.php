<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilePicture extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'storage_path',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected $hidden = [
        'user_id',
    ];

    /**
     * Get the post that owns the attachment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
