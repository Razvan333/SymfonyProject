FROM ubuntu:latest

FROM bitnami/php-fpm:latest

FROM mysql:latest

FROM rabbitmq:3.8-management-alpine

FROM redis:latest

RUN apt-get update && \
    apt-get install -y \
        zip \
        unzip \
        curl \
        && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql

RUN apt-get update && apt-get install -y default-mysql-client

RUN apt-get update && apt-get install -y rabbitmq-server

RUN apt-get update && apt-get install -y redis-server

RUN apt-get update && apt-get install -y php7.4-fpm

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

CMD ["php-fpm"]

CMD service mysql start && php-fpm
