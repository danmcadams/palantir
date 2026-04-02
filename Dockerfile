FROM php:8.4-cli
RUN apt-get update && apt-get install -y libzip-dev curl \
    && docker-php-ext-install zip \
    && mkdir -p /var/www/vendor \
    && curl -fsSL https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js \
       -o /var/www/vendor/mermaid.min.js \
    && rm -rf /var/lib/apt/lists/*
