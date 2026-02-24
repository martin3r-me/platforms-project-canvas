<?php

namespace Platform\ProjectCanvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class PcOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'project-canvas.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /project-canvas/overview - Zeigt Uebersicht ueber das Project Canvas Modul (Konzepte, Block-Typen, Entry-Typen, verfuegbare Tools).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $blockTypes = config('pc-templates.block_types', []);

            return ToolResult::success([
                'module' => 'project-canvas',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team bzw. team_id Parameter',
                ],
                'concepts' => [
                    'pc_canvases' => [
                        'model' => 'Platform\\ProjectCanvas\\Models\\PcCanvas',
                        'table' => 'pc_canvases',
                        'key_fields' => ['id', 'uuid', 'name', 'description', 'status', 'contextable_type', 'contextable_id', 'team_id'],
                        'note' => 'Ein Project Canvas zur Projektplanung. Enthaelt Building Blocks. Status: draft, active, archived.',
                    ],
                    'pc_building_blocks' => [
                        'model' => 'Platform\\ProjectCanvas\\Models\\PcBuildingBlock',
                        'table' => 'pc_building_blocks',
                        'key_fields' => ['id', 'uuid', 'pc_canvas_id', 'block_type', 'label', 'position'],
                        'note' => 'Die Building Blocks eines Project Canvas. Werden automatisch beim Canvas-Erstellen angelegt.',
                    ],
                    'pc_entries' => [
                        'model' => 'Platform\\ProjectCanvas\\Models\\PcEntry',
                        'table' => 'pc_entries',
                        'key_fields' => ['id', 'uuid', 'pc_building_block_id', 'title', 'content', 'entry_type', 'position', 'metadata'],
                        'note' => 'Einzelne Eintraege innerhalb eines Building Blocks. entry_type: text, date, person, amount.',
                    ],
                    'pc_canvas_snapshots' => [
                        'model' => 'Platform\\ProjectCanvas\\Models\\PcCanvasSnapshot',
                        'table' => 'pc_canvas_snapshots',
                        'key_fields' => ['id', 'uuid', 'pc_canvas_id', 'version', 'snapshot_data'],
                        'note' => 'Versionierte Snapshots eines Canvas fuer Vergleiche.',
                    ],
                ],
                'block_types' => collect($blockTypes)->map(fn ($def, $type) => [
                    'type' => $type,
                    'label' => $def['label'],
                    'description' => $def['description'],
                ])->values()->toArray(),
                'entry_types' => [
                    'text' => 'Freitext-Eingabe',
                    'date' => 'Datum oder Zeitraum',
                    'person' => 'Person oder Team',
                    'amount' => 'Betrag oder Anzahl',
                ],
                'relationships' => [
                    'canvas_has_blocks' => 'PcCanvas -> PcBuildingBlocks (automatisch bei Erstellung)',
                    'block_has_entries' => 'PcBuildingBlock -> PcEntries',
                    'canvas_has_snapshots' => 'PcCanvas -> PcCanvasSnapshots',
                ],
                'related_tools' => [
                    'canvases' => [
                        'list' => 'project-canvas.canvases.GET',
                        'get' => 'project-canvas.canvas.GET',
                        'create' => 'project-canvas.canvases.POST',
                        'update' => 'project-canvas.canvases.PUT',
                        'delete' => 'project-canvas.canvases.DELETE',
                    ],
                    'entries' => [
                        'list' => 'project-canvas.entries.GET',
                        'create' => 'project-canvas.entries.POST',
                        'update' => 'project-canvas.entries.PUT',
                        'delete' => 'project-canvas.entries.DELETE',
                        'bulk_create' => 'project-canvas.entries.bulk.POST',
                        'reorder' => 'project-canvas.entries.reorder.PUT',
                    ],
                    'snapshots' => [
                        'list' => 'project-canvas.snapshots.GET',
                        'create' => 'project-canvas.snapshots.POST',
                        'get' => 'project-canvas.snapshot.GET',
                        'compare' => 'project-canvas.snapshots.compare.GET',
                    ],
                    'utilities' => [
                        'export' => 'project-canvas.export.GET',
                        'status' => 'project-canvas.status.GET',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Project-Canvas-Uebersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'project-canvas', 'canvas'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
