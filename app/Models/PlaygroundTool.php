<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PlaygroundTool extends Model
{
    /** @use HasFactory<\Database\Factories\PlaygroundToolFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'component_name',
        'configuration',
        'is_active',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
    
    public function userData(): HasMany
    {
        return $this->hasMany(UserToolData::class, 'playground_tool_id');
    }
    
    public function userDataFor(User $user): ?UserToolData
    {
        return $this->userData()->where('user_id', $user->id)->first();
    }
    
    public function getOrCreateUserData(User $user): UserToolData
    {
        return $this->userDataFor($user) ?? UserToolData::create([
            'user_id' => $user->id,
            'playground_tool_id' => $this->id,
            'saved_data' => []
        ]);
    }
}
