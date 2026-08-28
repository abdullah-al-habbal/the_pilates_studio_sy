<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property array $data
 * @property string|null $image
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'type', 'title', 'message', 'data', 'image', 'read_at'])]
#[Translatable(['title', 'message'])]
class AppNotification extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'read_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
