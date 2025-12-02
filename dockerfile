# Use official PHP image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite (optional but useful)
RUN a2enmod rewrite

# Copy your project files to the container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose default Apache port
EXPOSE 80
