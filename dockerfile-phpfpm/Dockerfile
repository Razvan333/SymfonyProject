FROM php:7.4-fpm

RUN apt-get update && \
    apt-get install -y \
        zip \
        unzip \
        curl \
        && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

CMD ["php-fpm", "-F"]