<?php

namespace Taxi\Storage;

class JsonStorage
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
    }

    public function read(string $filename): array
    {
        $filePath = $this->storagePath . '/' . $filename;
        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        return json_decode($content, true) ?? [];
    }

    public function write(string $filename, array $data): void
    {
        $filePath = $this->storagePath . '/' . $filename;
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function append(string $filename, array $item): void
    {
        $data = $this->read($filename);
        $data[] = $item;
        $this->write($filename, $data);
    }

    public function update(string $filename, int $id, array $updates): bool
    {
        $data = $this->read($filename);
        foreach ($data as $key => $item) {
            if ($item['id'] === $id) {
                $data[$key] = array_merge($item, $updates);
                $this->write($filename, $data);
                return true;
            }
        }
        return false;
    }

    public function findById(string $filename, int $id): ?array
    {
        $data = $this->read($filename);
        foreach ($data as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        return null;
    }

    public function findAll(string $filename, ?callable $filter = null): array
    {
        $data = $this->read($filename);
        if ($filter === null) {
            return $data;
        }
        // Re-index so callers (and json_encode) always see a plain 0-based
        // list rather than a gappy array, which PHP encodes as a JSON object.
        return array_values(array_filter($data, $filter));
    }

    public function delete(string $filename, int $id): bool
    {
        $data = $this->read($filename);
        foreach ($data as $key => $item) {
            if ($item['id'] === $id) {
                unset($data[$key]);
                $this->write($filename, array_values($data));
                return true;
            }
        }
        return false;
    }

    public function getNextId(string $filename): int
    {
        $data = $this->read($filename);
        if (empty($data)) {
            return 1;
        }
        return max(array_column($data, 'id')) + 1;
    }
}
