# Stage 1: Base stage (PHP + Extensions)
FROM php:8.2-apache AS base
RUN apt-get update && apt-get install -y \
    openssl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Stage 2: Dependencies stage (Composer)
FROM composer:latest AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Stage 3: Build stage (SSL generation)
FROM alpine:latest AS build
RUN apk add --no-cache openssl
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/server.key \
    -out /etc/apache2/ssl/server.crt \
    -subj "/C=US/ST=State/L=City/O=Organization/OU=Department/CN=localhost"

# Stage 4: Runtime stage (Final)
FROM base AS runtime
WORKDIR /var/www/html

# Enable mod_rewrite, mod_headers, and mod_ssl
RUN a2enmod rewrite headers ssl

# Set AllowOverride to allow .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Create a basic SSL configuration for Apache
RUN printf "<VirtualHost *:443>\n\
    DocumentRoot /var/www/html\n\
    SSLEngine on\n\
    SSLCertificateFile /etc/apache2/ssl/server.crt\n\
    SSLCertificateKeyFile /etc/apache2/ssl/server.key\n\
    \n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/local-ssl.conf

# Enable the SSL site
RUN a2dissite default-ssl && a2ensite local-ssl

# Copy SSL certificates from build stage
COPY --from=build /etc/apache2/ssl /etc/apache2/ssl

# Copy vendor from dependencies stage
COPY --from=dependencies /app/vendor /var/www/html/vendor

# Copy the rest of the application
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
