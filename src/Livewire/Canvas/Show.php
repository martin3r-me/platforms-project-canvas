<?php

namespace Platform\ProjectCanvas\Livewire\Canvas;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\ProjectCanvas\Models\PcCanvas;
use Platform\ProjectCanvas\Services\PcStatusService;

class Show extends Component
{
    public PcCanvas $canvas;

    public function mount(PcCanvas $canvas): void
    {
        abort_unless($canvas->team_id === Auth::user()->currentTeam->id, 403);

        $this->canvas = $canvas;
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => 'PcCanvas',
            'modelId' => $this->canvas->id,
            'subject' => $this->canvas->name,
            'description' => 'Project Canvas',
            'url' => route('project-canvas.canvases.show', $this->canvas),
            'source' => 'project-canvas.canvases.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show'],
        ]);
    }

    public function render()
    {
        $this->canvas->load(['buildingBlocks.entries', 'createdByUser', 'snapshots']);

        $canvasData = $this->canvas->toCanvasArray();
        $statusData = (new PcStatusService())->assessStatus($this->canvas);

        $blockTypes = config('pc-templates.block_types', []);

        return view('project-canvas::livewire.canvas.show', [
            'canvasData' => $canvasData,
            'statusData' => $statusData,
            'blockTypes' => $blockTypes,
        ])->layout('platform::layouts.app');
    }
}
