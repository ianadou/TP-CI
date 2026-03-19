<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\StudentRepository;
use App\Validator\StudentValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1')]
class StudentController extends AbstractController
{
    public function __construct(
        private readonly StudentRepository $repository,
        private readonly StudentValidator $validator,
    ) {
    }

    #[Route('/students', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $students = array_map(fn ($s) => $s->toArray(), $this->repository->findAll());

        return $this->json($students);
    }

    // stats et search déclarés avant {id} pour éviter un conflit de routing
    #[Route('/students/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        return $this->json($this->repository->stats());
    }

    #[Route('/students/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->getString('q');

        if ('' === $query) {
            return $this->json(['error' => 'Le paramètre q est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $students = array_map(fn ($s) => $s->toArray(), $this->repository->search($query));

        return $this->json($students);
    }

    #[Route('/students', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $errors = $this->validator->validate($data);
        if ([] !== $errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        if (null !== $this->repository->findByEmail($data['email'])) {
            return $this->json(['error' => 'Un étudiant avec cet email existe déjà'], Response::HTTP_CONFLICT);
        }

        $student = $this->repository->create(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            (float) $data['grade'],
            $data['field'],
        );

        return $this->json($student->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/students/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'L\'identifiant doit être un entier positif'], Response::HTTP_BAD_REQUEST);
        }

        $student = $this->repository->findById((int) $id);

        if (null === $student) {
            return $this->json(['error' => 'Étudiant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($student->toArray());
    }
}
