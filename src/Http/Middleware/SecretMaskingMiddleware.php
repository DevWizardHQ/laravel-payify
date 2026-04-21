<?php

namespace DevWizard\Payify\Http\Middleware;

class SecretMaskingMiddleware
{
    /**
     * @param  string[]  $maskKeys  substrings matched case-insensitively against keys/header names
     */
    public function __construct(private array $maskKeys = []) {}

    public function maskPayload(array $data): array
    {
        if ($this->maskKeys === []) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskPayload($value);
            } elseif ($this->matches((string) $key)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }

    public function maskHeaders(array $headers): array
    {
        if ($this->maskKeys === []) {
            return $headers;
        }

        foreach ($headers as $name => $value) {
            if ($this->matches($name)) {
                $headers[$name] = '***';
            }
        }

        return $headers;
    }

    private function matches(string $name): bool
    {
        $lower = strtolower($name);
        $normalized = str_replace('-', '_', $lower);
        foreach ($this->maskKeys as $needle) {
            $needleLower = strtolower($needle);
            if (str_contains($lower, $needleLower) || str_contains($normalized, $needleLower)) {
                return true;
            }
        }

        return false;
    }
}
