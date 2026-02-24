<?php

namespace Platform\ProjectCanvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\ProjectCanvas\Models\PcCanvas;
use Platform\ProjectCanvas\Services\PcStatusService;
use Platform\ProjectCanvas\Tools\Concerns\ResolvesPcTeam;

class StatusTool implements ToolContract, ToolMetadataContract
{
    use ResolvesPcTeam;

    public function getName(): string
    {
        return 'project-canvas.status.GET';
    }

    public function getDescription(): string
    {
        return 'GET /project-canvas/status - Bewertet Canvas-Vollstaendigkeit und Health-Metriken (Ampel-Logik). ERFORDERLICH: canvas_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'canvas_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Canvas (ERFORDERLICH).',
                ],
            ],
            'required' => ['canvas_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $canvasId = (int)($arguments['canvas_id'] ?? 0);
            if ($canvasId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');
            }

            $canvas = PcCanvas::query()
                ->with(['buildingBlocks.entries'])
                ->where('team_id', $teamId)
                ->find($canvasId);

            if (!$canvas) {
                return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden (oder kein Zugriff).');
            }

            $statusService = new PcStatusService();
            $status = $statusService->assessStatus($canvas);

            return ToolResult::success($status);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bewerten des Canvas-Status: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['project-canvas', 'status', 'metrics'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
