FROM php:8.2-apache

# Enable mod_rewrite, mod_headers, and mod_ssl
RUN a2enmod rewrite headers ssl

# Install mysqli extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

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
