<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\StudentRepository;
use App\Validator\StudentValidator;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/v1')]
#[OA\Tag(name: 'Students')]
class StudentController extends AbstractController
{
    public function __construct(
        private readonly StudentRepository $repository,
        private readonly StudentValidator $validator,
    ) {
    }

    /** @var string[] */
    private const SORTABLE_FIELDS = ['id', 'firstName', 'lastName', 'email', 'grade', 'field'];

    #[Route('/students', methods: ['GET'])]
    #[OA\Get(
        path: '/v1/students',
        summary: 'Liste des étudiants avec pagination et tri',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'id', enum: ['id', 'firstName', 'lastName', 'email', 'grade', 'field'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée'),
            new OA\Response(response: 400, description: 'Paramètre invalide'),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));
        $sort = $request->query->getString('sort', 'id');
        $order = $request->query->getString('order', 'asc');

        if (!in_array($sort, self::SORTABLE_FIELDS, true)) {
            return $this->json(
                ['error' => 'Champ de tri invalide. Valeurs acceptées : '.implode(', ', self::SORTABLE_FIELDS)],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!in_array($order, ['asc', 'desc'], true)) {
            return $this->json(['error' => 'L\'ordre doit être "asc" ou "desc"'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->repository->findPaginated($page, $limit, $sort, $order));
    }

    // stats et search déclarés avant {id} pour éviter un conflit de routing
    #[Route('/students/stats', methods: ['GET'])]
    #[OA\Get(
        path: '/v1/students/stats',
        summary: 'Statistiques globales des étudiants',
        responses: [
            new OA\Response(response: 200, description: 'Stats : total, moyenne, meilleur étudiant, répartition par filière'),
        ]
    )]
    public function stats(): JsonResponse
    {
        return $this->json($this->repository->stats());
    }

    #[Route('/students/search', methods: ['GET'])]
    #[OA\Get(
        path: '/v1/students/search',
        summary: "Recherche d'étudiants par nom, prénom ou email",
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des étudiants correspondants'),
            new OA\Response(response: 400, description: 'Paramètre q manquant'),
        ]
    )]
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
    #[OA\Post(
        path: '/v1/students',
        summary: 'Créer un nouvel étudiant',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'email', 'grade', 'field'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Jean'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'email', type: 'string', example: 'jean.dupont@example.com'),
                    new OA\Property(property: 'grade', type: 'number', example: 14.5),
                    new OA\Property(property: 'field', type: 'string', example: 'informatique'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Étudiant créé'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 409, description: 'Email déjà utilisé'),
        ]
    )]
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
    #[OA\Get(
        path: '/v1/students/{id}',
        summary: "Détail d'un étudiant",
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Étudiant trouvé'),
            new OA\Response(response: 400, description: 'ID invalide'),
            new OA\Response(response: 404, description: 'Étudiant non trouvé'),
        ]
    )]
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

    #[Route('/students/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: '/v1/students/{id}',
        summary: 'Modifier un étudiant',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['firstName', 'lastName', 'email', 'grade', 'field'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string'),
                    new OA\Property(property: 'lastName', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'grade', type: 'number'),
                    new OA\Property(property: 'field', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Étudiant mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 404, description: 'Étudiant non trouvé'),
            new OA\Response(response: 409, description: 'Email déjà utilisé'),
        ]
    )]
    public function update(string $id, Request $request): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'L\'identifiant doit être un entier positif'], Response::HTTP_BAD_REQUEST);
        }

        $student = $this->repository->findById((int) $id);

        if (null === $student) {
            return $this->json(['error' => 'Étudiant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $errors = $this->validator->validate($data);
        if ([] !== $errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->repository->findByEmail($data['email']);
        if (null !== $existing && $existing->id !== $student->id) {
            return $this->json(['error' => 'Un étudiant avec cet email existe déjà'], Response::HTTP_CONFLICT);
        }

        $updated = $this->repository->update(
            $student,
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            (float) $data['grade'],
            $data['field'],
        );

        return $this->json($updated->toArray());
    }

    #[Route('/students/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/v1/students/{id}',
        summary: 'Supprimer un étudiant',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Étudiant supprimé'),
            new OA\Response(response: 404, description: 'Étudiant non trouvé'),
        ]
    )]
    public function delete(string $id): JsonResponse
    {
        if (!ctype_digit($id)) {
            return $this->json(['error' => 'L\'identifiant doit être un entier positif'], Response::HTTP_BAD_REQUEST);
        }

        $student = $this->repository->findById((int) $id);

        if (null === $student) {
            return $this->json(['error' => 'Étudiant non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->repository->delete((int) $id);

        return $this->json(['message' => 'Étudiant supprimé avec succès']);
    }
}
