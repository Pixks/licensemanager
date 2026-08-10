<?php

declare(strict_types=1);

namespace App\Validation;

use InvalidArgumentException;

final class Validator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', (string) $ruleSet);
            $value = $data[$field] ?? null;
            $nullable = in_array('nullable', $ruleList, true);
            if (($value === null || $value === '') && $nullable) continue;
            foreach ($ruleList as $rule) {
                $name = $rule; $parameter = null;
                if (str_contains((string) $rule, ':')) [$name, $parameter] = explode(':', (string) $rule, 2);
                if ($name === 'required' && ($value === null || $value === '')) $errors[$field][] = 'This field is required.';
                if ($value === null || $value === '') continue;
                match ($name) {
                    'string' => is_string($value) || $errors[$field][] = 'Must be a string.',
                    'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false || $errors[$field][] = 'Must be an integer.',
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) || $errors[$field][] = 'Must be a valid email.',
                    'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true) || $errors[$field][] = 'Must be true or false.',
                    'url' => filter_var($value, FILTER_VALIDATE_URL) || $errors[$field][] = 'Must be a valid URL.',
                    'date' => strtotime((string) $value) !== false || $errors[$field][] = 'Must be a valid date.',
                    'semver' => preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', (string) $value) || $errors[$field][] = 'Must be a valid semantic version.',
                    'domain' => preg_match('/^[a-z0-9*.-]+$/i', (string) $value) || $errors[$field][] = 'Must be a valid domain or wildcard.',
                    'max' => mb_strlen((string) $value) <= (int) $parameter || $errors[$field][] = 'Value is too long.',
                    'min' => mb_strlen((string) $value) >= (int) $parameter || $errors[$field][] = 'Value is too short.',
                    'in' => in_array((string) $value, array_map('trim', explode(',', (string) $parameter)), true) || $errors[$field][] = 'Value is not allowed.',
                    default => true,
                };
            }
        }
        if ($errors !== []) throw new InvalidArgumentException((string) json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $data;
    }
}
