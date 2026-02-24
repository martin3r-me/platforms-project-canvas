<?php

namespace Platform\ProjectCanvas\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\ProjectCanvas\Models\PcEntry;
use Platform\ProjectCanvas\Tools\Concerns\ResolvesPcTeam;

class UpdateEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesPcTeam;

    public function getName(): string
    {
        return 'project-canvas.entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /project-canvas/entries/{id} - Aktualisiert einen Project Canvas Entry. Parameter: entry_id (required). Optional: title, content, entry_type, position, metadata.';
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
                    'description' => 'ID des Entries (ERFORDERLICH). Nutze "project-canvas.entries.GET".',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Inhalt.',
                ],
                'entry_type' => [
                    'type' => 'string',
                    'enum' => ['text', 'date', 'person', 'amount'],
                    'description' => 'Optional: Neuer Entry-Typ (text, date, person, amount).',
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Position.',
                ],
                'metadata' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Metadaten (JSON).',
                ],
            ],
            'required' => ['entry_id'],
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

            foreach (['title', 'content', 'position'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $entry->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            if (array_key_exists('entry_type', $arguments)) {
                $entryType = $arguments['entry_type'];
                if (!in_array($entryType, ['text', 'date', 'person', 'amount'])) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungueltiger entry_type. Erlaubt: text, date, person, amount.');
                }
                $entry->entry_type = $entryType;
            }

            if (array_key_exists('metadata', $arguments)) {
                $entry->metadata = $arguments['metadata'];
            }

            $entry->save();

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'entry_type' => $entry->entry_type,
                'position' => $entry->position,
                'building_block_id' => $entry->pc_building_block_id,
                'message' => 'Entry erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['project-canvas', 'entries', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
