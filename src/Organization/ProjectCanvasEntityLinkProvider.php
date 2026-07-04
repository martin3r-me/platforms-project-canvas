<?php

namespace Platform\ProjectCanvas\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;
use Platform\ProjectCanvas\Models\PcCanvas;

class ProjectCanvasEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['pc_canvas'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'pc_canvas' => ['label' => 'Project Canvas', 'singular' => 'Project Canvas', 'icon' => 'clipboard-document-list', 'route' => null],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        $query->withCount('buildingBlocks');
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'status' => $model->status ?? null,
            'block_count' => (int) ($model->building_blocks_count ?? 0),
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'pc_canvas' => [
                ['field' => 'status', 'format' => 'badge'],
                ['field' => 'block_count', 'format' => 'count', 'suffix' => 'Blocks'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'pc_canvas') {
            return [];
        }

        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        $canvases = PcCanvas::whereIn('id', $allIds)
            ->withCount('buildingBlocks')
            ->select('id', 'status')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = 0;
            $active = 0;
            $archived = 0;
            $blocksTotal = 0;

            foreach ($ids as $id) {
                $canvas = $canvases[$id] ?? null;
                if (! $canvas) {
                    continue;
                }
                $total++;
                if ($canvas->status === 'active') {
                    $active++;
                } elseif ($canvas->status === 'archived') {
                    $archived++;
                }
                $blocksTotal += (int) ($canvas->building_blocks_count ?? 0);
            }

            $result[$entityId] = [
                'pc_canvas_total' => $total,
                'pc_canvas_active' => $active,
                'pc_canvas_archived' => $archived,
                'pc_canvas_blocks_total' => $blocksTotal,
            ];
        }

        return $result;
    }

    public function metricDefinitions(): array
    {
        return [
            'pc_canvas_total'        => ['label' => 'Project Canvases (gesamt)', 'group' => 'canvas', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'pc_canvas_active'       => ['label' => 'Project Canvases (aktiv)', 'group' => 'canvas', 'direction' => 'up', 'unit' => 'count', 'pair' => 'pc_canvas_total', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'pc_canvas_archived'     => ['label' => 'Project Canvases (archiviert)', 'group' => 'canvas', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'pc_canvas_blocks_total' => ['label' => 'Project-Canvas-Bausteine', 'group' => 'canvas', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'complexity', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
        ];
    }
}
