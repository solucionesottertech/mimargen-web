<?php
/**
 * JSON Storage
 *
 * Simple JSON file storage for leads and other platform data.
 * Matches OtterErp JsonStorage contract.
 *
 * Usage:
 *   $enc     = new Encryption(APP_SECRET);
 *   $storage = new JsonStorage($platformDir, $enc);
 *   $storage->insert('leads', [...]);
 */
class JsonStorage
{
    private string $dir;
    private ?Encryption $enc;

    /**
     * @param string $dir Directory to store JSON files
     * @param Encryption|null $enc Optional encryption wrapper
     */
    public function __construct(string $dir, ?Encryption $enc = null)
    {
        $this->dir = rtrim($dir, '/');
        $this->enc = $enc;
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0750, true);
        }
    }

    /**
     * Insert a record into a collection.
     *
     * @param string $collection Collection name (e.g. 'leads')
     * @param array<string, mixed> $data Record data
     * @return bool
     */
    public function insert(string $collection, array $data): bool
    {
        $file = $this->collectionFile($collection);

        $records = $this->readCollection($collection);
        $records[] = $data;

        return $this->writeCollection($file, $records);
    }

    /**
     * Load all records from a collection.
     *
     * @param string $collection Collection name
     * @return array<int, array<string, mixed>>
     */
    public function all(string $collection): array
    {
        return $this->readCollection($collection);
    }

    /**
     * Get the file path for a collection.
     */
    private function collectionFile(string $collection): string
    {
        return $this->dir . '/' . $collection . '.dat';
    }

    /**
     * Read and decode a collection file.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readCollection(string $collection): array
    {
        $file = $this->collectionFile($collection);
        if (!is_file($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }

        // Decrypt if encryption is available
        if ($this->enc !== null) {
            try {
                $content = $this->enc->decrypt($content);
            } catch (Throwable $e) {
                return [];
            }
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode and write a collection file.
     *
     * @param array<int, array<string, mixed>> $records
     */
    private function writeCollection(string $file, array $records): bool
    {
        $json = json_encode($records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        // Encrypt if encryption is available
        if ($this->enc !== null) {
            try {
                $json = $this->enc->encrypt($json);
            } catch (Throwable $e) {
                return false;
            }
        }

        return file_put_contents($file, $json, LOCK_EX) !== false;
    }
}
