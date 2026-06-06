<?php

namespace Trail\Support;

class StepContextKeys
{
    public static function fromCaller(int $argumentOffset = 1): array
    {
        if (! config('trail.steps.infer_variable_names', true)) {
            return [];
        }

        $frame = self::callerFrame();

        if (! $frame || empty($frame['file']) || empty($frame['line'])) {
            return [];
        }

        $source = self::callSource($frame['file'], (int) $frame['line']);

        if ($source === null) {
            return [];
        }

        return array_slice(self::variableArguments($source), $argumentOffset);
    }

    private static function callerFrame(): ?array
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (($frame['function'] ?? null) === 'step' && (($frame['class'] ?? null) === null)) {
                return $frame;
            }
        }

        return null;
    }

    private static function callSource(string $file, int $line): ?string
    {
        if (! is_file($file)) {
            return null;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $source = '';
        $depth = 0;
        $started = false;

        for ($index = $line - 1; $index < count($lines); $index++) {
            $source .= $lines[$index] . "\n";
            $tokens = token_get_all('<?php ' . $source);
            $started = $started || str_contains($source, 'step');
            $depth = self::parenthesisDepth($tokens);

            if ($started && $depth === 0 && str_contains($source, ');')) {
                return $source;
            }
        }

        return null;
    }

    private static function parenthesisDepth(array $tokens): int
    {
        $depth = 0;
        $seenStep = false;

        foreach ($tokens as $token) {
            $value = is_array($token) ? $token[1] : $token;

            if (is_array($token) && $token[0] === T_STRING && $value === 'step') {
                $seenStep = true;
            }

            if (! $seenStep) {
                continue;
            }

            if ($value === '(') {
                $depth++;
            } elseif ($value === ')') {
                $depth--;
            }
        }

        return $depth;
    }

    private static function variableArguments(string $source): array
    {
        $tokens = token_get_all('<?php ' . $source);
        $arguments = [];
        $current = [];
        $depth = 0;
        $collecting = false;
        $seenStep = false;

        foreach ($tokens as $token) {
            $value = is_array($token) ? $token[1] : $token;

            if (! $seenStep) {
                $seenStep = is_array($token) && $token[0] === T_STRING && $value === 'step';
                continue;
            }

            if (! $collecting) {
                if ($value === '(') {
                    $collecting = true;
                    $depth = 1;
                }
                continue;
            }

            if ($value === '(' || $value === '[') {
                $depth++;
            } elseif ($value === ')' || $value === ']') {
                $depth--;
            }

            if ($depth === 1 && $value === ',') {
                $arguments[] = self::variableName($current);
                $current = [];
                continue;
            }

            if ($depth === 0) {
                $arguments[] = self::variableName($current);
                break;
            }

            $current[] = $token;
        }

        return $arguments;
    }

    private static function variableName(array $tokens): ?string
    {
        $meaningful = array_values(array_filter($tokens, function ($token) {
            return ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
        }));

        if (count($meaningful) !== 1 || ! is_array($meaningful[0]) || $meaningful[0][0] !== T_VARIABLE) {
            return null;
        }

        return ltrim($meaningful[0][1], '$');
    }
}
