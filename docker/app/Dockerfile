FROM node:latest AS node
FROM php:8.4-fpm

ENV TZ Asia/Tokyo

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# install Library
RUN apt-get update && \
  apt-get install -y git unzip libzip-dev libicu-dev libonig-dev \
  libpng-dev libjpeg-dev libfreetype6-dev libpq-dev postgresql-client cron && \
  docker-php-ext-configure gd --with-freetype --with-jpeg && \
  docker-php-ext-install intl pdo_pgsql zip bcmath gd && \
  apt-get clean && rm -rf /var/lib/apt/lists/*


# config file COPY
COPY ./docker/app/php.ini /usr/local/etc/php/php.ini

# install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY ./docker/app/crontab /etc/cron.d/my-cron
RUN chmod 0644 /etc/cron.d/my-cron && \
  crontab /etc/cron.d/my-cron

CMD service cron start && php-fpm
