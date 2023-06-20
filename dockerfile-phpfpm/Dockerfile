FROM php:8.1-fpm

RUN apt-get update && \
    apt-get install -y \
        zip \
        unzip \
        curl \
        libzip-dev \
        librabbitmq-dev \
        libsocket++-dev \
        && rm -rf /var/lib/apt/lists/*

RUN apt-get install -y librabbitmq4
RUN pecl install amqp && docker-php-ext-enable amqp

RUN docker-php-ext-install bcmath

RUN docker-php-ext-install sockets

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install pdo_mysql

RUN docker-php-ext-install mysqli

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

CMD ["php-fpm", "-F"]