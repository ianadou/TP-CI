<?php

declare(strict_types=1);

namespace App\Model;

class Student
{
    public function __construct(
        public readonly int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public float $grade,
        public string $field,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'grade' => $this->grade,
            'field' => $this->field,
        ];
    }
}
