<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class StudentControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Sans ça, le kernel reboot entre chaque requête et efface l'état du repository
        $this->client->disableReboot();

        $repository = static::getContainer()->get(StudentRepository::class);
        assert($repository instanceof StudentRepository);
        $repository->reset();
    }

    // --- GET ---

    public function testListReturnsSuccess(): void
    {
        $this->client->request('GET', '/v1/students');

        $this->assertResponseIsSuccessful();
    }

    public function testListInitialCountIsFive(): void
    {
        $this->client->request('GET', '/v1/students');

        $this->assertCount(5, $this->decodeResponse());
    }

    public function testShowStudent(): void
    {
        $this->client->request('GET', '/v1/students/1');

        $this->assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        $this->assertSame(1, $data['id']);
        $this->assertSame('Alice', $data['firstName']);
    }

    public function testShowStudentNotFound(): void
    {
        $this->client->request('GET', '/v1/students/999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testShowStudentInvalidId(): void
    {
        $this->client->request('GET', '/v1/students/abc');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // --- POST ---

    public function testCreateStudent(): void
    {
        $this->requestJson('POST', '/v1/students', [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'grade' => 14.5,
            'field' => 'informatique',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertSame('Jean', $this->decodeResponse()['firstName']);
    }

    public function testCreateStudentMissingField(): void
    {
        $this->requestJson('POST', '/v1/students', ['firstName' => 'Jean']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateStudentInvalidGrade(): void
    {
        $this->requestJson('POST', '/v1/students', [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'jean@example.com',
            'grade' => 25.0,
            'field' => 'informatique',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateStudentDuplicateEmail(): void
    {
        $this->requestJson('POST', '/v1/students', [
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'alice.martin@example.com',
            'grade' => 14.5,
            'field' => 'informatique',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    // --- PUT ---

    public function testUpdateStudent(): void
    {
        $this->requestJson('PUT', '/v1/students/1', [
            'firstName' => 'Alice',
            'lastName' => 'Martin',
            'email' => 'alice.new@example.com',
            'grade' => 19.5,
            'field' => 'informatique',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(19.5, $this->decodeResponse()['grade']);
    }

    public function testUpdateStudentNotFound(): void
    {
        $this->requestJson('PUT', '/v1/students/999', [
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'email' => 'jean@example.com',
            'grade' => 14.0,
            'field' => 'informatique',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- DELETE ---

    public function testDeleteStudent(): void
    {
        $this->client->request('DELETE', '/v1/students/1');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/v1/students/1');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteStudentNotFound(): void
    {
        $this->client->request('DELETE', '/v1/students/999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- Stats / Search ---

    public function testStats(): void
    {
        $this->client->request('GET', '/v1/students/stats');

        $this->assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        $this->assertSame(5, $data['totalStudents']);
        $this->assertArrayHasKey('averageGrade', $data);
        $this->assertArrayHasKey('studentsByField', $data);
        $this->assertArrayHasKey('bestStudent', $data);
    }

    public function testSearch(): void
    {
        $this->client->request('GET', '/v1/students/search?q=alice');

        $this->assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        $this->assertCount(1, $data);
        $this->assertSame('Alice', $data[0]['firstName']);
    }

    public function testSearchMissingQuery(): void
    {
        $this->client->request('GET', '/v1/students/search');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // --- Helpers ---

    /** @param array<string, mixed> $data */
    private function requestJson(string $method, string $url, array $data): void
    {
        $this->client->request(
            $method,
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode($data)
        );
    }

    /** @return array<mixed> */
    private function decodeResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        assert(false !== $content);
        $decoded = json_decode($content, true);
        assert(is_array($decoded));

        return $decoded;
    }
}
