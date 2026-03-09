FROM php:8.2-apache

RUN docker-php-ext-install mysqli

WORKDIR /var/www/html

COPY . .

EXPOSE 8080

CMD ["apache2-foreground"]