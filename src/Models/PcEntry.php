<?php

namespace Platform\ProjectCanvas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class PcEntry extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'pc_entries';

    protected $fillable = [
        'uuid',
        'pc_building_block_id',
        'title',
        'content',
        'entry_type',
        'position',
        'metadata',
        'created_by_user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    public function buildingBlock(): BelongsTo
    {
        return $this->belongsTo(PcBuildingBlock::class, 'pc_building_block_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    // Entry Types

    public static function getEntryTypes(): array
    {
        return [
            'text' => 'Text',
            'date' => 'Date',
            'person' => 'Person',
            'amount' => 'Amount',
        ];
    }
}
