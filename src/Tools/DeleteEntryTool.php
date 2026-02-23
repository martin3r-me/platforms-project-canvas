<?php

namespace Platform\ProjectCanvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\ProjectCanvas\Models\PcEntry;
use Platform\ProjectCanvas\Tools\Concerns\ResolvesPcTeam;

class DeleteEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesPcTeam;

    public function getName(): string
    {
        return 'pc.entries.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /pc/entries/{id} - Soft-loescht einen Project Canvas Entry. Parameter: entry_id (required), confirm (required=true).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Entries (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu loeschen.',
                ],
            ],
            'required' => ['entry_id', 'confirm'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');
            }

            $found = $this->validateAndFindModel(
                $arguments,
                $context,
                'entry_id',
                PcEntry::class,
                'NOT_FOUND',
                'Entry nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var PcEntry $entry */
            $entry = $found['model'];

            $entry->load('buildingBlock.canvas');
            if ((int)$entry->buildingBlock->canvas->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Entry.');
            }

            $entryId = (int)$entry->id;
            $entryTitle = (string)$entry->title;

            $entry->delete();

            return ToolResult::success([
                'entry_id' => $entryId,
                'title' => $entryTitle,
                'message' => 'Entry soft-geloescht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['project-canvas', 'entries', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
