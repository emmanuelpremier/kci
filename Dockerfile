FROM php:8.2-apache
RUN docker-php-ext-install mysqli
# Production PHP settings: errors go to the logs, not to visitors
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Turn off folder listings
RUN sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/' /etc/apache2/apache2.conf
# Copy all your PHP/HTML/CSS files to the web folder
COPY . /var/www/html/

# Give Apache permission
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
