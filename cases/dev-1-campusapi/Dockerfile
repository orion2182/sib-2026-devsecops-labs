FROM php:8.2.4-cli-alpine3.18
WORKDIR /app
COPY app/ /app/
USER root
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/router.php"]
