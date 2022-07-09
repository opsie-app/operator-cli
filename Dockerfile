FROM php:8.1-cli-alpine

WORKDIR /app

RUN apk add icu-dev && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl

COPY builds/operator /app/operator

CMD ["/app/operator", "monitor"]
