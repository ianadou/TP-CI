# TP CI/CD — API REST Étudiants

[![CI](https://github.com/ianadou/TP-CI/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/ianadou/TP-CI/actions/workflows/ci.yml)

API REST de gestion d'un annuaire d'étudiants, construite avec Symfony 8.
Projet réalisé dans le cadre du cours CI/CD (M1 Full Stack).

## Stack

- PHP 8.4+
- Symfony 8
- PHPUnit 13
- PHP-CS-Fixer + PHPStan (niveau 8)
- GitHub Actions (CI/CD)
- Docker

## Prérequis

- [Docker](https://docs.docker.com/get-docker/) et Docker Compose

## Lancer le projet

```bash
docker compose up
```

L'API est disponible sur `http://localhost:8000`.

## Lancer les tests

```bash
docker compose run --rm app php bin/phpunit
```

## Endpoints

| Méthode | Route                    | Description          | Codes        |
|---------|--------------------------|----------------------|--------------|
| GET     | /v1/students             | Liste des étudiants  | 200          |
| GET     | /v1/students/{id}        | Détail d'un étudiant | 200, 404, 400|
| POST    | /v1/students             | Créer un étudiant    | 201, 400, 409|
| PUT     | /v1/students/{id}        | Modifier un étudiant | 200, 404, 400|
| DELETE  | /v1/students/{id}        | Supprimer            | 200, 404     |
| GET     | /v1/students/stats       | Statistiques         | 200          |
| GET     | /v1/students/search?q=   | Recherche            | 200, 400     |

## Modèle Étudiant

| Champ     | Type   | Contraintes                                              |
|-----------|--------|----------------------------------------------------------|
| id        | int    | Auto-généré                                              |
| firstName | string | Obligatoire, min 2 caractères                            |
| lastName  | string | Obligatoire, min 2 caractères                            |
| email     | string | Obligatoire, format valide, unique                       |
| grade     | float  | Obligatoire, entre 0 et 20                               |
| field     | string | informatique, mathématiques, physique, chimie            |
