#!/bin/bash

/etc/init.d/apache2 stop; #stop the server
rm continue; #This stops manageLoginAttempts.sh
#Log the server stopping
php -r "require '/var/www/html/classes.php'; LogFunc('server stopped');";
