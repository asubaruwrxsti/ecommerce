# Use the official Ubuntu base image
FROM ubuntu:latest

# Set environment variables to non-interactive
ENV DEBIAN_FRONTEND=noninteractive

# Update the system
RUN apt-get update -y

# Install Apache, PHP and MySQLi
RUN apt-get install -y apache2 php7.4 libapache2-mod-php7.4 sqlite3 php-sqlite3

# Copy the current directory contents into the container at /var/www/html/
COPY html /var/www/html/

# Expose port 80
EXPOSE 80

# Start Apache service
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]