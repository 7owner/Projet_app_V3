#!/usr/bin/env bash
set -o errexit

# Installer Composer manuellement
echo "📦 Installation de Composer..."
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
    >&2 echo 'ERROR: Invalid Composer installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

echo "✅ Composer installé avec succès."

# Installer les dépendances PHP
composer install --no-dev --optimize-autoloader

# Créer les répertoires nécessaires
mkdir -p var/cache var/log

# Nettoyer et réchauffer le cache Symfony
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Exécuter les migrations si la BDD est prête
php bin/console doctrine:migrations:migrate --no-interaction --env=prod || true
