<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class AppNotification extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title', 'message'];

    protected $fillable = ['user_id', 'title', 'message', 'data', 'read_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'data'    => 'array',
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
