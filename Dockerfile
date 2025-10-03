FROM php:8.2-apache

# Install dependencies and enable ext-soap
RUN apt-get update \
  && apt-get install -y libxml2-dev unzip git curl --no-install-recommends \
  && docker-php-ext-install soap \
  && a2enmod rewrite

# Serve from /var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY src/ /var/www/html/
# Copy composer.json (for laminas autodiscover) and install PHP dependencies
COPY composer.json /var/www/html/composer.json
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && composer install --no-dev --prefer-dist --working-dir=/var/www/html || true

RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
