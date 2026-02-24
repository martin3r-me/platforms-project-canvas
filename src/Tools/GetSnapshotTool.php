<?php

namespace Platform\ProjectCanvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\ProjectCanvas\Models\PcCanvasSnapshot;
use Platform\ProjectCanvas\Tools\Concerns\ResolvesPcTeam;

class GetSnapshotTool implements ToolContract, ToolMetadataContract
{
    use ResolvesPcTeam;

    public function getName(): string
    {
        return 'project-canvas.snapshot.GET';
    }

    public function getDescription(): string
    {
        return 'GET /project-canvas/snapshots/{id} - Ruft einen einzelnen Snapshot mit vollstaendigen Daten ab. Parameter: snapshot_id (required).';
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
                'snapshot_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Snapshots (ERFORDERLICH). Nutze "project-canvas.snapshots.GET".',
                ],
            ],
            'required' => ['snapshot_id'],
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

            $snapshotId = (int)($arguments['snapshot_id'] ?? 0);
            if ($snapshotId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'snapshot_id ist erforderlich.');
            }

            $snapshot = PcCanvasSnapshot::query()
                ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                ->find($snapshotId);

            if (!$snapshot) {
                return ToolResult::error('NOT_FOUND', 'Snapshot nicht gefunden (oder kein Zugriff).');
            }

            return ToolResult::success([
                'id' => $snapshot->id,
                'uuid' => $snapshot->uuid,
                'canvas_id' => $snapshot->pc_canvas_id,
                'version' => $snapshot->version,
                'snapshot_data' => $snapshot->snapshot_data,
                'created_by_user_id' => $snapshot->created_by_user_id,
                'created_at' => $snapshot->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Snapshots: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['project-canvas', 'snapshot', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
