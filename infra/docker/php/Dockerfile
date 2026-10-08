# Image applicative de développement : PHP 8.4 FPM + extensions requises par Laravel et PostgreSQL.
FROM php:8.4-fpm-alpine

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql redis intl zip bcmath opcache pcntl gd \
    && apk add --no-cache git unzip postgresql16-client

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/votalis.ini

WORKDIR /var/www/app

# Utilisateur non privilégié aligné sur l'UID/GID de l'hôte pour les volumes montés.
# Si l'UID ou le GID existe déjà dans l'image (ex. GID 20 sur macOS), on le réutilise.
ARG UID=1000
ARG GID=1000
RUN if ! getent group "${GID}" >/dev/null; then addgroup -g "${GID}" app; fi \
    && GROUP_NAME="$(getent group "${GID}" | cut -d: -f1)" \
    && if ! getent passwd "${UID}" >/dev/null; then adduser -D -u "${UID}" -G "${GROUP_NAME}" app; fi
USER ${UID}

CMD ["php-fpm"]
