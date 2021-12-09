FROM softonic/composer-rector:latest

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions \
    protobuf \
    bcmath \
    pcntl

