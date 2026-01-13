FROM composer:2

# Install build dependencies
RUN apk add --no-cache $PHPIZE_DEPS linux-headers musl-dev autoconf gcc g++ make

# Install required PHP extensions
RUN docker-php-ext-install sockets bcmath pcntl

# Install protobuf
RUN pecl install protobuf && docker-php-ext-enable protobuf

# Install PCOV for fast code coverage
RUN pecl install pcov && docker-php-ext-enable pcov

# Configure PCOV
RUN echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini

# Clean up
RUN apk del $PHPIZE_DEPS autoconf gcc g++ make

WORKDIR /app
