# Command to run gource on Dolibarr git project.

cd ~/git/dolibarr
gource --date-format "%d %b %Y" -logo doc/images/appicon_64.png --highlight-users --highlight-colour FFFF88 -s 0.1 -1280x720 -r 25 -title 'Dolibarr ERP CRM Genesis' --stop-at-end --filename-time 2 --user-image-dir build/gource/avatars --hide filenames


# To build a mp4 video
# Change -crf 1 to -crf 50 for max compression (best is 5)
cd ~/git/dolibarr
gource --date-format "%d %b %Y" -logo doc/images/appicon_64.png --highlight-users --highlight-colour FFFF88 -s 0.1 -1280x720 -r 25 -title 'Dolibarr ERP CRM Genesis' --stop-at-end --filename-time 2 --user-image-dir build/gource/avatars --hide filenames -o - | ffmpeg -y -r 25 -f image2pipe -vcodec ppm -i - -vcodec libx264 -preset superslow -pix_fmt yuv420p -crf 5 -threads 0 -bf 0 dolibarr_genesis.mp4 
