<?php

namespace Platform\ProjectCanvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class PcBuildingBlock extends Model
{
    protected $table = 'pc_building_blocks';

    protected $fillable = [
        'uuid',
        'pc_canvas_id',
        'block_type',
        'label',
        'position',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
        });
    }

    // Relationships

    public function canvas(): BelongsTo
    {
        return $this->belongsTo(PcCanvas::class, 'pc_canvas_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PcEntry::class, 'pc_building_block_id')->orderBy('position');
    }

    // Scopes

    public function scopeByType($query, string $type)
    {
        return $query->where('block_type', $type);
    }

    /**
     * Get guiding questions for this block type from config.
     */
    public function getGuidingQuestions(): array
    {
        return config("pc-templates.block_types.{$this->block_type}.guiding_questions", []);
    }
}
