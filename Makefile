.PHONY: start stop test coverage lint lint-fix stan shell

## Lance l'API sur http://localhost:8000
start:
	docker compose up

## Arrête les containers
stop:
	docker compose down

## Lance les tests PHPUnit
test:
	docker compose run --rm app php bin/phpunit

## Affiche le rapport de couverture de code
coverage:
	docker compose run --rm app php bin/phpunit --coverage-text --colors=never

## Vérifie le style de code (lecture seule)
lint:
	docker compose run --rm app ./vendor/bin/php-cs-fixer fix --dry-run --diff

## Corrige automatiquement le style de code
lint-fix:
	docker compose run --rm app ./vendor/bin/php-cs-fixer fix

## Lance l'analyse statique PHPStan
stan:
	docker compose run --rm app ./vendor/bin/phpstan analyse --no-progress

## Ouvre un shell dans le container
shell:
	docker compose run --rm app sh
