# TP CI/CD — API REST Étudiants

[![CI](https://github.com/ianadou/TP-CI/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/ianadou/TP-CI/actions/workflows/ci.yml)

API REST de gestion d'un annuaire d'étudiants, construite avec Symfony 8.
Projet réalisé dans le cadre du cours CI/CD (M1 Full Stack).

## Stack

- PHP 8.5
- Symfony 8
- PHPUnit 13 (25 tests)
- PHP-CS-Fixer + PHPStan (niveau 8)
- GitHub Actions (CI/CD) — matrix PHP 8.4 + 8.5, coverage PCOV
- Docker + Makefile

## Prérequis

- [Docker](https://docs.docker.com/get-docker/) et Docker Compose

## Commandes

| Commande        | Description                              |
|-----------------|------------------------------------------|
| `make start`    | Lance l'API sur `http://localhost:8000`  |
| `make stop`     | Arrête les containers                    |
| `make test`     | Lance les tests PHPUnit                  |
| `make coverage` | Affiche le rapport de couverture         |
| `make lint`     | Vérifie le style de code                 |
| `make lint-fix` | Corrige automatiquement le style         |
| `make stan`     | Lance l'analyse statique PHPStan         |
| `make shell`    | Ouvre un shell dans le container         |

> Sans `make` : toutes ces commandes utilisent Docker, aucune installation PHP locale requise.

## Documentation API

Une fois l'API lancée (`make start`), la documentation interactive Swagger est disponible sur :

**`http://localhost:8000/api-doc.html`**

Le schéma OpenAPI 3.0 brut est disponible sur `http://localhost:8000/api/doc.json`.

## Endpoints

| Méthode | Route                    | Description          | Codes              |
|---------|--------------------------|----------------------|--------------------|
| GET     | /v1/students             | Liste paginée + tri  | 200, 400           |
| GET     | /v1/students/{id}        | Détail d'un étudiant | 200, 404, 400      |
| POST    | /v1/students             | Créer un étudiant    | 201, 400, 409      |
| PUT     | /v1/students/{id}        | Modifier un étudiant | 200, 404, 400, 409 |
| DELETE  | /v1/students/{id}        | Supprimer            | 200, 404, 400      |
| GET     | /v1/students/stats       | Statistiques         | 200                |
| GET     | /v1/students/search?q=   | Recherche            | 200, 400           |

### Pagination et tri (GET /v1/students)

| Paramètre | Type   | Défaut | Valeurs acceptées                                    |
|-----------|--------|--------|------------------------------------------------------|
| `page`    | int    | 1      | ≥ 1                                                  |
| `limit`   | int    | 10     | 1–100                                                |
| `sort`    | string | id     | id, firstName, lastName, email, grade, field         |
| `order`   | string | asc    | asc, desc                                            |

## Modèle Étudiant

| Champ     | Type   | Contraintes                                              |
|-----------|--------|----------------------------------------------------------|
| id        | int    | Auto-généré                                              |
| firstName | string | Obligatoire, min 2 caractères                            |
| lastName  | string | Obligatoire, min 2 caractères                            |
| email     | string | Obligatoire, format valide, unique                       |
| grade     | float  | Obligatoire, entre 0 et 20                               |
| field     | string | informatique, mathématiques, physique, chimie            |

## Workflow GitFlow

```text
feat/** / fix/**  →  (CI verte)  →  develop  →  (workflow_dispatch)  →  main
```

- Les branches `feat/` et `fix/` sont **auto-mergées** dans `develop` après CI verte
- La release vers `main` est **manuelle** via GitHub Actions → Run workflow
