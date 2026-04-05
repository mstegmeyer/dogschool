#!/bin/sh

set -eu

STAMP_FILE='node_modules/.docker-install-stamp'

if [ ! -d 'node_modules/nuxt' ] || [ ! -f "$STAMP_FILE" ] || [ 'package-lock.json' -nt "$STAMP_FILE" ] || [ 'package.json' -nt "$STAMP_FILE" ]; then
    npm install
    touch "$STAMP_FILE"
fi

exec npm run dev
