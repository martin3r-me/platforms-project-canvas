<?php

namespace Platform\ProjectCanvas\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;

class ProjectCanvasEntityLinkProvider implements EntityLinkProvider
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
        return [];
    }
}
