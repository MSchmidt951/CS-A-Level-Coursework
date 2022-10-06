#!/bin/bash

#start the server
/etc/init.d/apache2 start;
#start the script that manages the number of login attempts left
./manageLoginAttempts.sh &;
#Log that the server started
php -r "require '/var/www/html/classes.php'; LogFunc('server started');";
#Make the log file be able to be edited by the PHP scripts
logFile="$(date +"%d-%m-%Y.log")";
chmod 666 ../Logs/$logFile;
