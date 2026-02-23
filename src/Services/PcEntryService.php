<?php

namespace Platform\ProjectCanvas\Services;

use Platform\ProjectCanvas\Models\PcBuildingBlock;
use Platform\ProjectCanvas\Models\PcEntry;

class PcEntryService
{
    /**
     * Create a single entry in a building block.
     */
    public function createEntry(PcBuildingBlock $block, array $data): PcEntry
    {
        if (!isset($data['position'])) {
            $data['position'] = ($block->entries()->max('position') ?? 0) + 1;
        }

        return $block->entries()->create($data);
    }

    /**
     * Bulk create entries in a building block.
     *
     * @return array<PcEntry>
     */
    public function bulkCreateEntries(PcBuildingBlock $block, array $entriesData, int $userId): array
    {
        $maxPosition = $block->entries()->max('position') ?? 0;
        $created = [];

        foreach ($entriesData as $data) {
            $maxPosition++;
            $created[] = $block->entries()->create([
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'entry_type' => $data['entry_type'] ?? 'text',
                'position' => $data['position'] ?? $maxPosition,
                'metadata' => $data['metadata'] ?? null,
                'created_by_user_id' => $userId,
            ]);
        }

        return $created;
    }

    /**
     * Reorder entries within a building block.
     *
     * @param array<int> $entryIds Ordered list of entry IDs
     */
    public function reorderEntries(PcBuildingBlock $block, array $entryIds): void
    {
        foreach ($entryIds as $position => $entryId) {
            $block->entries()
                ->where('id', $entryId)
                ->update(['position' => $position + 1]);
        }
    }
}
