<?php

namespace Platform\ProjectCanvas\Services;

use Platform\Core\Contracts\CanvasOptionsProviderInterface;
use Platform\ProjectCanvas\Models\PcCanvas;

class CoreCanvasOptionsProvider implements CanvasOptionsProviderInterface
{
    public function options(?int $teamId, ?string $query = null, int $limit = 20): array
    {
        if (!$teamId) {
            return [];
        }

        $q = PcCanvas::query()
            ->forTeam($teamId)
            ->orderBy('name');

        if ($query) {
            $like = '%' . $query . '%';
            $q->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('description', 'like', $like);
            });
        }

        return $q->limit($limit)->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->name])
            ->all();
    }
}
