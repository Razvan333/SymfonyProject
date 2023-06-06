FROM ubuntu:latest

RUN apt-get update && \
    apt-get install -y \
        zip \
        unzip \
        curl \
        php7.4-fpm \
        default-mysql-client \
        rabbitmq-server \
        redis-server \
        && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

CMD service mysql start && php-fpm -F
