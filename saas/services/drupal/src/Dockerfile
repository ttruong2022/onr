# FROM php:7.4-apache

# RUN apt-get update \
#   && apt-get install -y libxml2-dev libzip-dev libbz2-dev freetds-dev libmariadb-dev libonig-dev libxslt-dev libcurl4-openssl-dev libpng-dev libmagickwand-dev imagemagick \
#   && ln -s /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/ \
#   && docker-php-ext-install dom xmlwriter tokenizer simplexml ctype zip bz2 exif pdo mysqli pdo_dblib pdo_mysql xml session mbstring iconv gmp xmlrpc soap xsl curl gd

# # Build and enable imagemagick
# RUN pecl install imagick
# RUN docker-php-ext-enable imagick

# # Install text editors
# RUN apt-get install -y nano vim

# # Remove apt cache
# RUN rm -rf /var/lib/apt/lists/*

FROM mobomo/drupalstand-ci:8.0-latest AS ci

COPY / /root/
WORKDIR /root/
RUN npm install -g gulp
RUN composer install
RUN ln -s /root/vendor/bin/robo /bin/robo
RUN /root/vendor/bin/robo build:theme

FROM mobomo/drupalstand-prod:php-8.0.13

COPY --chown=www-data:www-data --from=ci /root /var/www/
ENV APACHE_DOCUMENT_ROOT /var/www/webroot
RUN sed -i 's/\/var\/www\/html/\/var\/www\/webroot/g' /etc/apache2/sites-available/*.conf

ENV PATH=$PATH:/var/www/vendor/bin
WORKDIR /var/www/webroot