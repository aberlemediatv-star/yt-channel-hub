#!/bin/sh
# Wird von gh als GH_BROWSER aufgerufen (URL als Argumente).
exec /usr/bin/open -a Safari "$1"
