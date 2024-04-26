FROM php:8.2-alpine3.19

RUN apk add --no-cache postgresql-dev \
	&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
	&& docker-php-ext-install -j$(nproc) pdo_pgsql pgsql \
	&& docker-php-ext-enable pdo_pgsql pgsql

ENTRYPOINT ["tail", "-f", "/dev/null"]
