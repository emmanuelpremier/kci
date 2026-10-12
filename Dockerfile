   FROM php:8.2-apache
   RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libwebp-dev && rm -rf /var/lib/apt/lists/*
   RUN docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install gd mysqli exif
   # Production PHP settings: errors go to the logs, not to visitors
   RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
   # Allow picture uploads (the browser shrinks big photos first)
   RUN printf "upload_max_filesize=8M\npost_max_size=10M\nmemory_limit=256M\n" > /usr/local/etc/php/conf.d/uploads.ini
   # Turn off folder listings
   RUN sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/' /etc/apache2/apache2.conf
   # Copy all your PHP/HTML/CSS files to the web folder
   COPY . /var/www/html/

   # Give Apache permission
   RUN chown -R www-data:www-data /var/www/html
   EXPOSE 80