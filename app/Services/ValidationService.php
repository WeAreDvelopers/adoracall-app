<?php

namespace App\Services;

/**
 * Centralized input validation service for common data types
 */
class ValidationService
{
    /**
     * Validate and format Brazilian CPF
     * Removes non-numeric characters and validates length
     */
    public static function validateCpf($cpf): ?string
    {
        if (!$cpf) {
            return null;
        }

        // Remove non-numeric characters
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Must be 11 digits
        if (strlen($cpf) !== 11) {
            return null;
        }

        // Reject all same digits (common invalid format)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return null;
        }

        return $cpf;
    }

    /**
     * Validate and format Brazilian phone number
     * Extracts DDD and validates format (11 digits total)
     */
    public static function validatePhone($phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Must be 10 or 11 digits (2 DDD + 8-9 number)
        if (strlen($phone) < 10 || strlen($phone) > 11) {
            return null;
        }

        // Validate DDD (area code) is valid (11-99 range)
        $ddd = (int) substr($phone, 0, 2);
        if ($ddd < 11 || $ddd > 99) {
            return null;
        }

        return $phone;
    }

    /**
     * Extract DDD (area code) from phone number
     */
    public static function extractDdd($phone): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) < 2) {
            return null;
        }

        $ddd = substr($phone, 0, 2);
        return (int) $ddd >= 11 ? $ddd : null;
    }

    /**
     * Validate email format
     */
    public static function validateEmail($email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Validate date format (dd/mm/yyyy or yyyy-mm-dd)
     */
    public static function validateDate($date, $format = 'Y-m-d'): ?string
    {
        if (!$date) {
            return null;
        }

        $date = trim($date);

        // Try to parse various date formats
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd/m/y',
            'm/d/Y',
            'm/d/y',
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
            'd-m-Y',
            'd.m.Y',
            'n/j/y',    // 1/20/86
            'n/j/Y',    // 1/20/2086
            'j/n/y',    // 20/1/86
            'j/n/Y',    // 20/1/2086
        ];

        foreach ($formats as $fmt) {
            $parsed = \DateTime::createFromFormat($fmt, $date);
            if ($parsed && $parsed->format($fmt) === $date) {
                // Para anos com 2 dígitos, ajustar se ficou no futuro (ex: 86 → 2086 → 1986)
                if ($parsed->format('Y') > date('Y')) {
                    $parsed->modify('-100 years');
                }
                return $parsed->format('Y-m-d');
            }
        }

        // Fallback: tentar strtotime como última tentativa
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            $parsed = new \DateTime('@' . $timestamp);
            if ($parsed->format('Y') > date('Y')) {
                $parsed->modify('-100 years');
            }
            return $parsed->format('Y-m-d');
        }

        return null;
    }

    /**
     * Validate numeric value within range
     */
    public static function validateNumericRange($value, $min = 0, $max = PHP_INT_MAX): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $num = (float) $value;

        if ($num < $min || $num > $max) {
            return null;
        }

        return $num;
    }

    /**
     * Validate and sanitize string (remove special chars, limit length)
     */
    public static function validateString($value, $minLength = 1, $maxLength = 255): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (strlen($value) < $minLength || strlen($value) > $maxLength) {
            return null;
        }

        // Remove potentially harmful characters but keep basic alphanumeric + spaces
        $value = preg_replace('/[^\pL\pN\s\-\.\,\(\)]/u', '', $value);

        return !empty($value) ? $value : null;
    }

    /**
     * Validate company name (CNPJ or business name)
     */
    public static function validateCompanyName($name): ?string
    {
        if (!$name) {
            return null;
        }

        $name = trim($name);

        // Must be between 3-100 characters
        if (strlen($name) < 3 || strlen($name) > 100) {
            return null;
        }

        return $name;
    }

    /**
     * Validate percentage value (0-100)
     */
    public static function validatePercentage($value): ?float
    {
        $num = self::validateNumericRange($value, 0, 100);
        return $num !== null ? $num : null;
    }

    /**
     * Get error message for validation field
     */
    public static function getErrorMessage($field, $type): string
    {
        $messages = [
            'cpf_invalid' => 'CPF deve conter exatamente 11 dígitos válidos',
            'phone_invalid' => 'Telefone deve ter 10 ou 11 dígitos com DDD válido (11-99)',
            'email_invalid' => 'Email deve estar em formato válido',
            'date_invalid' => 'Data deve estar em formato DD/MM/YYYY ou YYYY-MM-DD',
            'numeric_invalid' => 'Valor deve ser numérico',
            'string_invalid' => 'Valor de texto é obrigatório',
            'percentage_invalid' => 'Percentual deve estar entre 0 e 100',
            'company_invalid' => 'Nome da empresa deve ter entre 3 e 100 caracteres',
        ];

        return $messages[$type] ?? 'Campo inválido';
    }
}
