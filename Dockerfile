# Use the official Ubuntu base image
FROM ubuntu:latest

# Set environment variables to non-interactive
ENV DEBIAN_FRONTEND=noninteractive

# Update the system and install necessary packages
RUN apt-get update -y && \
	apt-get install -y software-properties-common curl zip unzip && \
	add-apt-repository ppa:ondrej/php && \
	apt-get update -y && \
	apt-get install -y apache2 php8.2 libapache2-mod-php8.2 sqlite3 php8.2-sqlite3 php8.2-dom php8.2-curl && \
	curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
	apt-get clean && \
	rm -rf /var/lib/apt/lists/*

# Verify Composer installation
RUN composer --version

# Set the working directory
WORKDIR /var/www/html/

# Copy the contents of the html directory into /var/www/html/
COPY html/ /var/www/html/

# Run Composer Install
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi

# Take ownership of the directory
RUN chown -R www-data:www-data /var/www/html/

# Set the ServerName directive globally
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && service apache2 restart

RUN rm /var/www/html/index.html

# Expose port 80
EXPOSE 80

# Start Apache service
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]