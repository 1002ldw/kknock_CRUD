#!/bin/sh
set -eu

for file in ./*.php ./docker/*.php; do
    php -l "$file" >/dev/null
done

docker compose config >/dev/null
git diff --check

printf '%s\n' 'PHP lint, Compose config, and Git diff checks passed.'
