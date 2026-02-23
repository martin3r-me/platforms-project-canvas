<?php

namespace Platform\ProjectCanvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\ProjectCanvas\Models\PcBuildingBlock;
use Platform\ProjectCanvas\Services\PcEntryService;
use Platform\ProjectCanvas\Tools\Concerns\ResolvesPcTeam;

class CreateEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesPcTeam;

    public function getName(): string
    {
        return 'pc.entries.POST';
    }

    public function getDescription(): string
    {
        return 'POST /pc/entries - Erstellt einen Entry in einem Building Block. ERFORDERLICH: building_block_id, title. Optional: content, entry_type (text/date/person/amount, default: text), position, metadata.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'building_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Building Blocks (ERFORDERLICH).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel des Entries (ERFORDERLICH).',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Optional: Inhalt/Beschreibung.',
                ],
                'entry_type' => [
                    'type' => 'string',
                    'enum' => ['text', 'date', 'person', 'amount'],
                    'description' => 'Optional: Entry-Typ (text, date, person, amount). Default: text.',
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional: Position (auto-increment wenn nicht angegeben).',
                ],
                'metadata' => [
                    'type' => 'object',
                    'description' => 'Optional: Zusaetzliche Metadaten (JSON).',
                ],
            ],
            'required' => ['building_block_id', 'title'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $blockId = (int)($arguments['building_block_id'] ?? 0);
            if ($blockId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');
            }

            $block = PcBuildingBlock::query()
                ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                ->find($blockId);

            if (!$block) {
                return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden (oder kein Zugriff).');
            }

            $title = trim((string)($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $entryType = $arguments['entry_type'] ?? 'text';
            if (!in_array($entryType, ['text', 'date', 'person', 'amount'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungueltiger entry_type. Erlaubt: text, date, person, amount.');
            }

            $entryService = new PcEntryService();
            $entry = $entryService->createEntry($block, [
                'title' => $title,
                'content' => $arguments['content'] ?? null,
                'entry_type' => $entryType,
                'position' => $arguments['position'] ?? null,
                'metadata' => $arguments['metadata'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'entry_type' => $entry->entry_type,
                'position' => $entry->position,
                'building_block_id' => $entry->pc_building_block_id,
                'message' => 'Entry erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['project-canvas', 'entries', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
