#!/bin/sh

set -eu

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --no-interaction
fi

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction
fi

exec "$@"
