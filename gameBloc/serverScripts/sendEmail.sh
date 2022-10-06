#!/bin/bash

#Clean the data
header="${1//___/ }";
body="${2//___/ }";
body="${body//N__N/$'\n'}";

#Send the email
ssmtp 'administrators@gamebloc.com' <<< 'To: administrators@gamebloc.com
From: bot@gamebloc.com
Subject: '$header$'\n
Hi Admins,\n\nGame bloc bot here!\n\n'$body &;

