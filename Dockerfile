FROM php:8.2-apache

# Install dependencies and enable ext-soap
RUN apt-get update \
  && apt-get install -y libxml2-dev unzip git --no-install-recommends \
  && docker-php-ext-install soap \
  && a2enmod rewrite

# Serve from /var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY src/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
