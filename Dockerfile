FROM composer/composer:2.4.0

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions \
    protobuf \
    bcmath \
    pcntl \
    sockets

