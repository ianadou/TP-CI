<?php

declare(strict_types=1);

namespace App\Validator;

class StudentValidator
{
    /** @var string[] */
    private const VALID_FIELDS = ['informatique', 'mathématiques', 'physique', 'chimie'];

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        return array_merge(
            $this->validateFirstName($data['firstName'] ?? null),
            $this->validateLastName($data['lastName'] ?? null),
            $this->validateEmail($data['email'] ?? null),
            $this->validateGrade($data['grade'] ?? null),
            $this->validateField($data['field'] ?? null),
        );
    }

    /** @return array<string, string> */
    private function validateFirstName(mixed $value): array
    {
        if (null === $value || '' === $value) {
            return ['firstName' => 'Le prénom est obligatoire'];
        }

        if (strlen((string) $value) < 2) {
            return ['firstName' => 'Le prénom doit contenir au moins 2 caractères'];
        }

        return [];
    }

    /** @return array<string, string> */
    private function validateLastName(mixed $value): array
    {
        if (null === $value || '' === $value) {
            return ['lastName' => 'Le nom est obligatoire'];
        }

        if (strlen((string) $value) < 2) {
            return ['lastName' => 'Le nom doit contenir au moins 2 caractères'];
        }

        return [];
    }

    /** @return array<string, string> */
    private function validateEmail(mixed $value): array
    {
        if (null === $value || '' === $value) {
            return ['email' => "L'email est obligatoire"];
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ['email' => "L'email n'est pas valide"];
        }

        return [];
    }

    /** @return array<string, string> */
    private function validateGrade(mixed $value): array
    {
        if (null === $value) {
            return ['grade' => 'La note est obligatoire'];
        }

        if (!is_numeric($value) || (float) $value < 0 || (float) $value > 20) {
            return ['grade' => 'La note doit être entre 0 et 20'];
        }

        return [];
    }

    /** @return array<string, string> */
    private function validateField(mixed $value): array
    {
        if (null === $value || '' === $value) {
            return ['field' => 'La filière est obligatoire'];
        }

        if (!in_array($value, self::VALID_FIELDS, true)) {
            return ['field' => 'La filière doit être parmi : '.implode(', ', self::VALID_FIELDS)];
        }

        return [];
    }
}
