<?php

namespace App\Services\TallyIntegration;

use App\Models\TallyFieldMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TallyMappingService
{
    private ?Collection $mappings = null;

    /**
     * Resolve source path (project field) for a given Tally target field.
     * Priority:
     * 1) Exact entity-prefixed mapping, e.g. "item.name" => "NAME"
     * 2) First mapping matching target field irrespective of entity
     * 3) Default source path
     */
    public function sourcePathForTarget(string $entity, string $targetField, string $defaultSourcePath): string
    {
        $allMappings = $this->getMappings();
        if ($allMappings->isEmpty()) {
            return $defaultSourcePath;
        }

        $target = strtoupper(trim($targetField));

        $matching = $allMappings->filter(function (TallyFieldMapping $mapping) use ($target) {
            return strtoupper(trim((string) $mapping->tally_field)) === $target;
        });

        if ($matching->isEmpty()) {
            return $defaultSourcePath;
        }

        $entityPrefix = $entity.'.';

        $selected = $matching->first(function (TallyFieldMapping $mapping) use ($entityPrefix) {
            return str_starts_with(trim((string) $mapping->project_field), $entityPrefix);
        }) ?? $matching->first();

        if (! $selected) {
            return $defaultSourcePath;
        }

        $sourcePath = trim((string) $selected->project_field);
        if ($sourcePath === '') {
            return $defaultSourcePath;
        }

        if (str_starts_with($sourcePath, $entityPrefix)) {
            $sourcePath = substr($sourcePath, strlen($entityPrefix));
        }

        return $sourcePath !== '' ? $sourcePath : $defaultSourcePath;
    }

    public function valueForTarget(string $entity, mixed $source, string $targetField, string $defaultSourcePath, mixed $fallback = null): mixed
    {
        $sourcePath = $this->sourcePathForTarget($entity, $targetField, $defaultSourcePath);
        $value = data_get($source, $sourcePath);

        return $value !== null ? $value : $fallback;
    }

    private function getMappings(): Collection
    {
        if ($this->mappings !== null) {
            return $this->mappings;
        }

        if (! Schema::hasTable('tally_field_mappings')) {
            $this->mappings = collect();

            return $this->mappings;
        }

        $this->mappings = TallyFieldMapping::query()
            ->select(['id', 'project_field', 'tally_field'])
            ->get();

        return $this->mappings;
    }
}

