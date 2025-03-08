<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'data',
        'is_read',
        'related_user_id',
        'related_type',
        'related_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
    ];

    /**
     * Bildirimin sahibi kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * İlgili kullanıcı
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * İlgili modeli al (Polimorfik ilişki)
     */
    public function related()
    {
        if ($this->related_type && $this->related_id) {
            $modelClass = 'App\\Models\\' . $this->related_type;
            return $modelClass::find($this->related_id);
        }
        return null;
    }
}
