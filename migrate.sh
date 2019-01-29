#!/usr/bin/env sh

set -eu

migrationsDirectory=build/migrations/
if [[ -d ${migrationsDirectory} ]]; then
    rm -rf ${migrationsDirectory}
fi

php test/TalisOrm/DoctrineMigrations/doctrine-migrations.php migrations:diff | grep "Generated new migration class"
php test/TalisOrm/DoctrineMigrations/doctrine-migrations.php migrations:migrate --no-interaction
php test/TalisOrm/DoctrineMigrations/doctrine-migrations.php migrations:diff | grep "No changes detected"
