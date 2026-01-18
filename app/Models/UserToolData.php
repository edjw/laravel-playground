<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserToolData extends Model
{
    protected $fillable = [
        'user_id',
        'playground_tool_id',
        'saved_data',
    ];

    protected function casts(): array
    {
        return [
            'saved_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(PlaygroundTool::class, 'playground_tool_id');
    }
}
