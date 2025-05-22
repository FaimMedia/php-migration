FROM php:8.4-alpine3.21

RUN apk add --no-cache postgresql-dev $PHPIZE_DEPS \
	&& pecl install xdebug
	&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
	&& docker-php-ext-install -j$(nproc) pdo_pgsql pgsql \
	&& docker-php-ext-enable pdo_pgsql pgsql xdebug \
	&& apk del $PHPIZE_DEPS

COPY ./docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /app

COPY . /app

ENTRYPOINT ["tail", "-f", "/dev/null"]
