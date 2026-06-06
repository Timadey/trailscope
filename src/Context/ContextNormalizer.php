<?php

namespace Trail\Context;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Trail\Support\Sanitizer;

class ContextNormalizer
{
    public function __construct(private Sanitizer $sanitizer)
    {
    }

    public function normalize(array $context, bool $includeStackTrace = false, array $keys = []): array
    {
        $normalized = [];

        foreach ($context as $rawKey => $value) {
            $index = count($normalized);
            $position = $index + 1;
            $key = is_string($rawKey) ? $rawKey : ($keys[$index] ?? $this->keyFor($value, $position));
            $normalized[$key] = $this->normalizeValue($value, $includeStackTrace);
        }

        return $normalized;
    }

    private function keyFor(mixed $value, int $position): string
    {
        if ($value instanceof Throwable) {
            return "exception_{$position}";
        }

        if ($value instanceof Model) {
            return "model_{$position}";
        }

        if ($value instanceof Request) {
            return "request_{$position}";
        }

        if ($value instanceof Response) {
            return "response_{$position}";
        }

        if (is_array($value)) {
            return "context_{$position}";
        }

        if (is_scalar($value) || $value === null) {
            return "value_{$position}";
        }

        return "object_{$position}";
    }

    private function normalizeValue(mixed $value, bool $includeStackTrace): mixed
    {
        if ($value instanceof Throwable) {
            $normalized = [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];

            if ($includeStackTrace) {
                $normalized['trace'] = $value->getTraceAsString();
            }

            return $this->sanitizer->clean($normalized);
        }

        if ($value instanceof Model) {
            $attributes = $value->getVisible()
                ? $value->only($value->getVisible())
                : $value->only($value->getFillable());

            return [
                'class' => $value::class,
                'id' => $value->getKey(),
                'attributes' => $this->sanitizer->clean($attributes),
            ];
        }

        if ($value instanceof Request) {
            return [
                'method' => $value->method(),
                'path' => $value->path(),
                'route' => optional($value->route())->getName(),
                'input' => $this->sanitizer->clean($value->all()),
            ];
        }

        if ($value instanceof Response) {
            return [
                'status' => $value->getStatusCode(),
                'content' => $this->sanitizer->clean($value->getContent()),
            ];
        }

        if (is_array($value) || is_scalar($value) || $value === null) {
            return $this->sanitizer->clean($value);
        }

        return [
            'class' => $value::class,
            'properties' => $this->sanitizer->clean(get_object_vars($value)),
        ];
    }
}
