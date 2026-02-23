<?php

namespace Platform\ProjectCanvas\Livewire\Canvas;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\ProjectCanvas\Models\PcCanvas;
use Platform\ProjectCanvas\Services\PcStatusService;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => null,
            'modelId' => null,
            'subject' => 'Project Canvas',
            'description' => 'Canvas-Übersicht',
            'url' => route('project-canvas.canvases.index'),
            'source' => 'project-canvas.canvases.index',
            'recipients' => [],
            'meta' => ['view_type' => 'index'],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $teamId = $team?->id;

        $query = PcCanvas::forTeam($teamId)
            ->withCount('buildingBlocks')
            ->with(['createdByUser', 'buildingBlocks.entries']);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }

        $canvases = $query->orderBy('updated_at', 'desc')->paginate(15);

        // Calculate status for each canvas on the current page
        $statusService = new PcStatusService();
        $canvasStatuses = [];
        foreach ($canvases as $canvas) {
            $canvasStatuses[$canvas->id] = $statusService->assessStatus($canvas);
        }

        $stats = [
            'total' => PcCanvas::forTeam($teamId)->count(),
            'draft' => PcCanvas::forTeam($teamId)->byStatus('draft')->count(),
            'active' => PcCanvas::forTeam($teamId)->byStatus('active')->count(),
            'archived' => PcCanvas::forTeam($teamId)->byStatus('archived')->count(),
        ];

        return view('project-canvas::livewire.canvas.index', [
            'canvases' => $canvases,
            'canvasStatuses' => $canvasStatuses,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
