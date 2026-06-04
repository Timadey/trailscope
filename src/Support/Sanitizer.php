<?php

namespace Trail\Support;

class Sanitizer
{
    public function __construct(
        private array $sensitiveKeys,
        private string $mask,
        private int $maxStringLength = 8192,
    ) {
    }

    public function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->cleanArray($value);
        }

        if (is_object($value)) {
            return $this->cleanArray(get_object_vars($value));
        }

        if (is_string($value) && strlen($value) > $this->maxStringLength) {
            return substr($value, 0, $this->maxStringLength) . '...[truncated]';
        }

        return $value;
    }

    private function cleanArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $clean[$key] = $this->mask;
                continue;
            }

            $clean[$key] = $this->clean($value);
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (str_contains(strtolower($key), strtolower((string) $sensitiveKey))) {
                return true;
            }
        }

        return false;
    }
}
