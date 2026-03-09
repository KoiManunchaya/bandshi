FROM php:8.2-apache

RUN docker-php-ext-install mysqli

WORKDIR /app

COPY . /app

RUN rm -rf /var/www/html/*
RUN ln -s /app /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]