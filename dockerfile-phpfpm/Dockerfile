FROM php:8.1-fpm

RUN apt-get update && \
    apt-get install -y \
        zip \
        unzip \
        curl \
        libzip-dev \
        && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql

RUN docker-php-ext-install mysqli

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

CMD ["php-fpm", "-F"]