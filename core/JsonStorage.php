<?php
/**
 * MiMargen JSON Storage
 *
 * Simple JSON file storage for leads and other platform data.
 */
class JsonStorage
{
    /**
     * Save a record to a JSON file in the data directory.
     *
     * @param string $collection e.g. 'leads'
     * @param array<string, mixed> $record
     * @return bool
     */
    public static function save(string $collection, array $record): bool
    {
        $dir = DATA_DIR . '/' . $collection;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $id = $record['id'] ?? bin2hex(random_bytes(8));
        $file = $dir . '/' . $id . '.json';

        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Load all records from a collection.
     *
     * @param string $collection e.g. 'leads'
     * @return array<int, array<string, mixed>>
     */
    public static function load(string $collection): array
    {
        $dir = DATA_DIR . '/' . $collection;
        if (!is_dir($dir)) {
            return [];
        }

        $records = [];
        $files = glob($dir . '/*.json');
        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $json = file_get_contents($file);
            if ($json === false) {
                continue;
            }
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $records[] = $decoded;
            }
        }

        return $records;
    }
}
