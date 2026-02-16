# wp-patrol
Simple SIEM tool for WordPress administrators. Run as PHP CLI script.

## whatis
This is PHP CLI script written primarily for inspecting security aspects of multiple WordPress instalation in a server (not WP-MU).

## howto
* Login as root (or any user with read access to WP directories).
* On terminal, type: *php wp-patrol.php [dirname]*

## alert
Most likely you'll need to run this script as root. Always read the script before using.

## change log
Features in initial version:
* Scan existing WP installations.
* Display active themes and plugins
* Display list of users with administrator and editor privileges.

## todo
This is the plan:
* Display suspicious wp-cron jobs.
