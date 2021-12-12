FROM php:8.1-cli-alpine

WORKDIR /app

COPY builds/operator /app/operator

CMD ["/app/operator", "monitor"]
