FROM php:8.4-cli
RUN apt-get update && apt-get install -y libzip-dev && docker-php-ext-install zip && rm -rf /var/lib/apt/lists/*
