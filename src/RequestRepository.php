<?php
declare(strict_types=1);

namespace App;

final class RequestRepository
{
    public function __construct(private readonly string $directory)
    {
    }

    public function save(string $id, array $data): void
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
        $target = $this->path($id);
        $temporary = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
        file_put_contents($temporary, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($temporary, $target);
    }

    public function find(string $id): ?array
    {
        if (!preg_match('/^[a-f0-9]{16}$/', $id) || !is_file($this->path($id))) {
            return null;
        }
        $data = json_decode((string)file_get_contents($this->path($id)), true);
        return is_array($data) ? $data : null;
    }

    private function path(string $id): string
    {
        return rtrim($this->directory, '/') . '/' . $id . '.json';
    }
}
