#!/bin/bash

readonly LARAVEL_APP_NAME=laravel-vite-heroku-travel-app

declare -ar ENVIRONMENT_ARR=(
    "APP_NAME=\"\$\{APP_NAME\"\}"
    "APP_ENV=production"
    "APP_DEBUG=true"
    "LOG_CHANNEL=stack"
    "LOG_DEPRECATIONS_CHANNEL=null"
    "LOG_LEVEL=debug"
    "BROADCAST_DRIVER=log"
    "CACHE_DRIVER=file"
    "FILESYSTEM_DISK=local"
    "QUEUE_CONNECTION=sync"
    "SESSION_DRIVER=database"
    "SESSION_LIFETIME=120"
    "SESSION_SECURE_COOKIE=true"
    "MEMCACHED_HOST=127.0.0.1"
    "REDIS_HOST=127.0.0.1"
    "REDIS_PASSWORD=null"
    "REDIS_PORT=6379"
    "MAIL_MAILER=smtp"
    "MAIL_HOST=mailpit"
    "MAIL_PORT=1025"
    "MAIL_USERNAME=null"
    "MAIL_PASSWORD=null"
    "MAIL_ENCRYPTION=null"
    "MAIL_FROM_ADDRESS=\"hello@example.com\""
    "MAIL_FROM_NAME=\"\$\{APP_NAME\}\""
    "AWS_ACCESS_KEY_ID="
    "AWS_SECRET_ACCESS_KEY="
    "AWS_DEFAULT_REGION=us-east-1"
    "AWS_BUCKET="
    "AWS_USE_PATH_STYLE_ENDPOINT=false"
    "PUSHER_APP_ID="
    "PUSHER_APP_KEY="
    "PUSHER_APP_SECRET="
    "PUSHER_HOST="
    "PUSHER_PORT=443"
    "PUSHER_SCHEME=https"
    "PUSHER_APP_CLUSTER=mt1"
    "VITE_APP_NAME=\"\$\{APP_NAME\}\""
    "VITE_PUSHER_APP_KEY=\"\$\{PUSHER_APP_KEY\}\""
    "VITE_PUSHER_HOST=\"\$\{PUSHER_HOST\}\""
    "VITE_PUSHER_PORT=\"\$\{PUSHER_PORT\}\""
    "VITE_PUSHER_SCHEME=\"\$\{PUSHER_SCHEME\}\""
    "VITE_PUSHER_APP_CLUSTER=\"\$\{PUSHER_APP_CLUSTER\}\""
)

for environment_data in "${ENVIRONMENT_ARR[@]}"; do
  heroku config:set "${environment_data}" -a ${LARAVEL_APP_NAME}
done

# 書き換えて下さい
heroku config:set APP_KEY=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set APP_URL=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set DB_CONNECTION=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set DB_HOST=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set DB_PORT=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set DB_DATABASE=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set DB_USERNAME=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set DB_PASSWORD=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set SESSION_DOMAIN=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set SUPABASE_URL=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set SUPABASE_API_KEY=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set GITHUB_REDIRECT_URI=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set GITHUB_CLIENT_ID=[TODO] -a ${LARAVEL_APP_NAME}
heroku config:set GITHUB_CLIENT_SECRET=[TODO] -a ${LARAVEL_APP_NAME}
