FROM php:8.1-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Apache should serve the "public" folder as document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (needed for our routing)
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY . /var/www/html
RUN mkdir -p /var/www/html/storage /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]