FROM dunglas/frankenphp:php8.4

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl git unzip graphviz default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_mysql intl zip pcntl bcmath opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node is needed in the container because php artisan dev also runs Vite
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Run as the host user so files written to the bind mount stay editable on the host
RUN groupadd --gid ${GID} app \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash app \
    && chown -R app:app /data /config

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER app
WORKDIR /app

EXPOSE 8000 5173

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "dev", "--inline"]
