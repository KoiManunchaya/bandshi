FROM php:8.2-apache

RUN docker-php-ext-install mysqli

RUN a2dismod mpm_event
RUN a2enmod mpm_prefork

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]