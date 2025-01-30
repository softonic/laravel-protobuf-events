FROM composer:2.2

RUN apk add --no-cache linux-headers musl-dev autoconf gcc g++ make

RUN docker-php-ext-install sockets bcmath pcntl

RUN pecl install protobuf \
    && docker-php-ext-enable protobuf

RUN apk del autoconf gcc g++ make

