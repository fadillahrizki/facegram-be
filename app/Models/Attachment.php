<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'storage_path',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected $hidden = [
        'post_id',
    ];

    /**
     * Get the post that owns the attachment.
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
