<?php

namespace Platform\ProjectCanvas\Services;

use Platform\Core\Contracts\CanvasForContextProviderInterface;
use Platform\ProjectCanvas\Models\PcCanvas;

class CoreCanvasForContextProvider implements CanvasForContextProviderInterface
{
    public function forContext(string $contextType, int $contextId): array
    {
        return PcCanvas::query()
            ->where('contextable_type', $contextType)
            ->where('contextable_id', $contextId)
            ->orderBy('name')
            ->get()
            ->map(function ($canvas) {
                return [
                    'id' => $canvas->id,
                    'name' => $canvas->name,
                    'status' => $canvas->status,
                    'url' => route('project-canvas.canvases.show', ['canvas' => $canvas->id]),
                ];
            })
            ->all();
    }
}
