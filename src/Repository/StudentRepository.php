<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Student;

class StudentRepository
{
    /** @var array<int, Student> */
    private array $students = [];

    private int $nextId = 1;

    public function __construct()
    {
        $this->seed();
    }

    /** @return Student[] */
    public function findAll(): array
    {
        return array_values($this->students);
    }

    /** @return array<string, mixed> */
    public function findPaginated(int $page, int $limit, string $sort, string $order): array
    {
        $students = array_values($this->students);

        usort($students, function (Student $a, Student $b) use ($sort, $order): int {
            $valA = match ($sort) {
                'id' => $a->id,
                'firstName' => $a->firstName,
                'lastName' => $a->lastName,
                'email' => $a->email,
                'grade' => $a->grade,
                default => $a->field,
            };
            $valB = match ($sort) {
                'id' => $b->id,
                'firstName' => $b->firstName,
                'lastName' => $b->lastName,
                'email' => $b->email,
                'grade' => $b->grade,
                default => $b->field,
            };
            $result = $valA <=> $valB;

            return 'desc' === $order ? -$result : $result;
        });

        $total = count($students);
        $items = array_slice($students, ($page - 1) * $limit, $limit);

        return [
            'data' => array_map(fn (Student $s) => $s->toArray(), $items),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function findById(int $id): ?Student
    {
        return $this->students[$id] ?? null;
    }

    public function findByEmail(string $email): ?Student
    {
        foreach ($this->students as $student) {
            if ($student->email === $email) {
                return $student;
            }
        }

        return null;
    }

    public function create(string $firstName, string $lastName, string $email, float $grade, string $field): Student
    {
        $student = new Student($this->nextId++, $firstName, $lastName, $email, $grade, $field);
        $this->students[$student->id] = $student;

        return $student;
    }

    public function update(Student $student, string $firstName, string $lastName, string $email, float $grade, string $field): Student
    {
        $student->firstName = $firstName;
        $student->lastName = $lastName;
        $student->email = $email;
        $student->grade = $grade;
        $student->field = $field;

        return $student;
    }

    public function delete(int $id): void
    {
        unset($this->students[$id]);
    }

    /** @return array<string, mixed> */
    public function stats(): array
    {
        $total = count($this->students);

        if (0 === $total) {
            return [
                'totalStudents' => 0,
                'averageGrade' => 0.0,
                'studentsByField' => [],
                'bestStudent' => null,
            ];
        }

        $sum = 0.0;
        /** @var array<string, int> */
        $byField = [];
        $best = null;

        foreach ($this->students as $student) {
            $sum += $student->grade;
            $byField[$student->field] = ($byField[$student->field] ?? 0) + 1;

            if (null === $best || $student->grade > $best->grade) {
                $best = $student;
            }
        }

        return [
            'totalStudents' => $total,
            'averageGrade' => round($sum / $total, 2),
            'studentsByField' => $byField,
            'bestStudent' => $best->toArray(),
        ];
    }

    /** @return Student[] */
    public function search(string $query): array
    {
        $q = strtolower($query);

        return array_values(array_filter(
            $this->students,
            fn (Student $s) => str_contains(strtolower($s->firstName), $q)
                || str_contains(strtolower($s->lastName), $q)
                || str_contains(strtolower($s->email), $q)
        ));
    }

    // Réinitialise l'état entre chaque test — le process HTTP meurt naturellement après chaque requête
    public function reset(): void
    {
        $this->students = [];
        $this->nextId = 1;
        $this->seed();
    }

    private function seed(): void
    {
        $this->create('Alice', 'Martin', 'alice.martin@example.com', 15.5, 'informatique');
        $this->create('Bob', 'Dupont', 'bob.dupont@example.com', 12.0, 'mathématiques');
        $this->create('Clara', 'Durand', 'clara.durand@example.com', 18.0, 'physique');
        $this->create('David', 'Bernard', 'david.bernard@example.com', 9.5, 'chimie');
        $this->create('Emma', 'Petit', 'emma.petit@example.com', 16.0, 'informatique');
    }
}
