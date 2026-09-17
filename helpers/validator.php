<?php
/**
 * Request Validation & Input Sanitization Helper
 */

class Validator {
    public static function sanitize(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    public static function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return $_POST ?: [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ($_POST ?: []);
    }

    public static function validate(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                if ($rule === 'required') {
                    if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                    }
                } elseif ($rule === 'email') {
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = 'Please provide a valid email address.';
                    }
                } elseif ($rule === 'numeric') {
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a numeric value.';
                    }
                } elseif ($rule === 'date') {
                    if (!empty($value)) {
                        $d = DateTime::createFromFormat('Y-m-d', $value);
                        if (!$d || $d->format('Y-m-d') !== $value) {
                            $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be in YYYY-MM-DD format.';
                        }
                    }
                } elseif (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (!empty($value) && strlen((string)$value) < $min) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.";
                    }
                } elseif (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (!empty($value) && strlen((string)$value) > $max) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.";
                    }
                }
            }
        }

        return $errors;
    }
}
