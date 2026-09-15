<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The email is never serialised to the public page.
     *
     * @var list<string>
     */
    protected $hidden = ['author_email', 'ip_hash'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicProps(): array
    {
        return [
            'id' => $this->id,
            'authorName' => $this->author_name,
            'body' => $this->body,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
