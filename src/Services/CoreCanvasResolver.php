<?php

namespace Platform\ProjectCanvas\Services;

use Platform\Core\Contracts\CanvasResolverInterface;
use Platform\ProjectCanvas\Models\PcCanvas;

class CoreCanvasResolver implements CanvasResolverInterface
{
    public function displayName(?int $canvasId): ?string
    {
        if (!$canvasId) {
            return null;
        }

        return PcCanvas::find($canvasId)?->name;
    }

    public function url(?int $canvasId): ?string
    {
        if (!$canvasId) {
            return null;
        }

        $canvas = PcCanvas::find($canvasId);
        if (!$canvas) {
            return null;
        }

        return route('project-canvas.canvases.show', ['canvas' => $canvas->id]);
    }

    public function status(?int $canvasId): ?string
    {
        if (!$canvasId) {
            return null;
        }

        return PcCanvas::find($canvasId)?->status;
    }
}
