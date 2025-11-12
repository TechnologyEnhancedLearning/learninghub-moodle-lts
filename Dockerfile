# Use the base image
FROM moodlehq/moodle-php-apache:8.3

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the current directory contents into the container
COPY . /var/www/html

COPY php.ini /usr/local/etc/php/php.ini

COPY 10-docker-php-moodle.ini /usr/local/etc/php/conf.d/

# Expose port 80 to the outside world
EXPOSE 80

# Start the Apache server
CMD ["apache2-foreground"]
