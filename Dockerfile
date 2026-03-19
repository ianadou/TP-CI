FROM php:8.5-cli-alpine

# libxml2-dev pour ext-xml, oniguruma-dev pour ext-mbstring (requis sur Alpine)
RUN apk add --no-cache libxml2-dev oniguruma-dev \
    && docker-php-ext-install xml mbstring

# On copie Composer depuis son image officielle plutôt que de le télécharger
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app


COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-progress --no-scripts

COPY . .

# Régénère l'autoloader optimisé maintenant que src/ est copié
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
