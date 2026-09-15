<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Composition extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function audioUrl(): ?string
    {
        return $this->audio_path ? Storage::disk('public')->url($this->audio_path) : null;
    }

    public function scoreUrl(): ?string
    {
        return $this->score_path ? Storage::disk('public')->url($this->score_path) : null;
    }

    public function scoreIsPdf(): bool
    {
        return $this->score_mime === 'application/pdf';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlayerProps(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'audioUrl' => $this->audioUrl(),
            'durationSeconds' => $this->duration_seconds,
            'scoreUrl' => $this->scoreUrl(),
            'scoreIsPdf' => $this->scoreIsPdf(),
        ];
    }
}
