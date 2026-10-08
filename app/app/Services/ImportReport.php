<?php

namespace App\Services;

final class ImportReport
{
    public int $imported = 0;

    public bool $written = false;

    /** @var list<array{line: int, field: string, message: string}> */
    public array $errors = [];

    public function addError(int $line, string $field, string $message): void
    {
        $this->errors[] = ['line' => $line, 'field' => $field, 'message' => $message];
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
