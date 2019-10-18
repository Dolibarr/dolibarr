# Command to run gource on Dolibarr git project.

cd ~/git/dolibarr
gource -logo doc/images/appicon_64.png --highlight-users --highlight-colour FFFF88 -s 0.2 -1280x720 -r 25 -title 'Dolibarr ERP CRM Genesis' --stop-at-end --filename-time 2 --user-image-dir build/gource/avatars