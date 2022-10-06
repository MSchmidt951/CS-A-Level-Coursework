#!/bin/bash

#set up the files and variables
touch "continue";
echo '5' > attempts;
loop=true;

#Every hour check if the continue file exists
#when checked either stop the loop or reset the number of login attempts left
while (($loop == true )); do
	sleep 1h;
	if [ -f 'continue' ]; then
		loop=false; #If the continue file doesnt exist stop the loop
	else
		echo '5' > attempts; #Reset the number of login attempts to 5
	fi
done

#Remove the attempts file
#This ensures there will be no errors next time the server is started
rm attempts;

