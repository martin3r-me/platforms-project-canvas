<?php

namespace Platform\ProjectCanvas\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Platform\ProjectCanvas\Models\PcCanvas;

class CanvasPdfController extends Controller
{
    public function __invoke(PcCanvas $canvas)
    {
        abort_unless(
            Auth::check() && $canvas->team_id === Auth::user()->currentTeam?->id,
            403,
            'Zugriff verweigert'
        );

        $canvas->load(['buildingBlocks.entries', 'createdByUser']);

        $canvasData = $canvas->toCanvasArray();
        $blockTypes = config('pc-templates.block_types', []);

        $fontScale = $this->calculateFontScale($canvasData);

        $html = view('project-canvas::pdf.canvas', [
            'canvas' => $canvas,
            'canvasData' => $canvasData,
            'blockTypes' => $blockTypes,
            'fontScale' => $fontScale,
        ])->render();

        $filename = str($canvas->name ?: 'project-canvas')
            ->slug('-')
            ->append('.pdf')
            ->toString();

        return Pdf::loadHTML($html)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * Calculate font scale based on total content volume.
     *
     * Returns a scale key: 'lg', 'md', 'sm', 'xs'
     */
    private function calculateFontScale(array $canvasData): string
    {
        $totalChars = 0;
        $totalEntries = 0;

        foreach ($canvasData['blocks'] ?? [] as $block) {
            foreach ($block['entries'] ?? [] as $entry) {
                $totalEntries++;
                $totalChars += mb_strlen($entry['title'] ?? '');
                $totalChars += mb_strlen($entry['content'] ?? '');
            }
        }

        // Thresholds tuned for A4 landscape with 3x3 grid
        if ($totalChars < 800 && $totalEntries <= 18) {
            return 'lg';
        }

        if ($totalChars < 1800 && $totalEntries <= 36) {
            return 'md';
        }

        if ($totalChars < 3500 && $totalEntries <= 60) {
            return 'sm';
        }

        return 'xs';
    }
}
