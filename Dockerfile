FROM php:8.2-apache

# Instala extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Cambiar el DocumentRoot de Apache al directorio public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Ajusta la configuración de Apache para usar el nuevo DocumentRoot y habilita rewrite
RUN sed -ri "s!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf \
 && sed -ri "s!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g" /etc/apache2/apache2.conf \
 && a2enmod rewrite \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Copia el código al contenedor
COPY . /var/www/html/