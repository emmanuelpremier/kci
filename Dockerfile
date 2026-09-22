FROM php:8.2-apache

# Copy all your PHP/HTML/CSS files to the web folder
COPY . /var/www/html/

# Give Apache permission
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
