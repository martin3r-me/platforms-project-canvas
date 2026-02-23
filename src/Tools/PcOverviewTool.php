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
        return 'pc.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /pc/overview - Zeigt Uebersicht ueber das Project Canvas Modul (Konzepte, Block-Typen, Entry-Typen, verfuegbare Tools).';
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
                        'list' => 'pc.canvases.GET',
                        'get' => 'pc.canvas.GET',
                        'create' => 'pc.canvases.POST',
                        'update' => 'pc.canvases.PUT',
                        'delete' => 'pc.canvases.DELETE',
                    ],
                    'entries' => [
                        'list' => 'pc.entries.GET',
                        'create' => 'pc.entries.POST',
                        'update' => 'pc.entries.PUT',
                        'delete' => 'pc.entries.DELETE',
                        'bulk_create' => 'pc.entries.bulk.POST',
                        'reorder' => 'pc.entries.reorder.PUT',
                    ],
                    'snapshots' => [
                        'list' => 'pc.snapshots.GET',
                        'create' => 'pc.snapshots.POST',
                        'get' => 'pc.snapshot.GET',
                        'compare' => 'pc.snapshots.compare.GET',
                    ],
                    'utilities' => [
                        'export' => 'pc.export.GET',
                        'status' => 'pc.status.GET',
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
