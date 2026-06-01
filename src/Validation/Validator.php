<?php

declare(strict_types=1);

namespace App\Validation;

class Validator
{
    protected array $errors = [];

    public function validate(array $data, array $schema): array
    {
        $this->errors = [];

        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? null;

            foreach ($rules as $rule) {
                $error = $this->applyRule($rule, $field, $value, $data);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break; // Stop at first error per field
                }
            }
        }

        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function applyRule(array $rule, string $field, $value, array $data): ?string
    {
        $type = $rule['type'];
        $message = $rule['message'] ?? 'Field tidak valid.';

        switch ($type) {
            case 'required':
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    return $message;
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $message;
                }
                break;

            case 'minLength':
                $min = $rule['min'];
                if (!empty($value) && mb_strlen(trim((string) $value)) < $min) {
                    return $message;
                }
                break;

            case 'maxLength':
                $max = $rule['max'];
                if (!empty($value) && mb_strlen(trim((string) $value)) > $max) {
                    return $message;
                }
                break;

            case 'phoneId':
                if (!empty($value) && !preg_match('/^(08|628|\+628)[0-9]{8,12}$/', (string) $value)) {
                    return $message;
                }
                break;

            case 'strongPassword':
                if (!empty($value)) {
                    $hasLetter = preg_match('/[a-zA-Z]/', (string) $value);
                    $hasNumber = preg_match('/[0-9]/', (string) $value);
                    if (!$hasLetter || !$hasNumber) {
                        return $message;
                    }
                }
                break;

            case 'sameAs':
                $otherField = $rule['field'];
                $otherValue = $data[$otherField] ?? null;
                if ((string) $value !== (string) $otherValue) {
                    return $message;
                }
                break;

            case 'oneOf':
                $options = $rule['options'];
                if (!empty($value) && !in_array($value, $options, true)) {
                    return $message;
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    return $message;
                }
                break;

            case 'minNumber':
                $min = $rule['min'];
                if (is_numeric($value) && (int) $value < $min) {
                    return $message;
                }
                break;

            case 'maxNumber':
                $max = $rule['max'];
                if (is_numeric($value) && (int) $value > $max) {
                    return $message;
                }
                break;

            case 'when':
                $predicate = $rule['predicate'];
                $innerRule = $rule['rule'];
                if ($predicate($data)) {
                    return $this->applyRule($innerRule, $field, $value, $data);
                }
                break;
        }

        return null;
    }

    public static function sanitize(array $data, array $allowedFields): array
    {
        $sanitized = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $val = $data[$field];
                if (is_string($val)) {
                    $val = trim(strip_tags($val));
                }
                $sanitized[$field] = $val;
            }
        }
        return $sanitized;
    }
}
