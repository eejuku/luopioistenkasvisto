#!/bin/bash

# ASETUKSET
PROD_HOST="puhde"
PROD_URL="https://puhde.com"
LOCAL_URL="http://localhost:8080"
DB_KONTTI="kasvisto-db-1"
AIKALEIMA=$(date +"%Y-%m-%d_%H%M")
TIEDOSTON_NIMI="push_to_prod_${AIKALEIMA}.sql"

echo "⚠️ Vie kanta TUOTANTOON ($PROD_URL)?"
read -p "y/n: " CONFIRM
if [[ $CONFIRM != [yY] ]]; then exit 1; fi

echo "📦 1/4 Luodaan varmuuskopio DB-kontista..."
docker exec $DB_KONTTI mysqldump -u wpuser -psalasana123 wordpress > $TIEDOSTON_NIMI

echo "🚚 2/4 Siirretään tiedosto palvelimelle..."
scp $TIEDOSTON_NIMI $PROD_HOST:/tmp/

echo "🚜 3/4 Tuodaan kanta tuotantoon..."
ssh $PROD_HOST "wp db import /tmp/$TIEDOSTON_NIMI --path=/var/www/html --allow-root"

echo "🔄 4/4 Päivitetään osoitteet..."
ssh $PROD_HOST "wp search-replace '$LOCAL_URL' '$PROD_URL' --path=/var/www/html --allow-root"

echo "🚀 Valmis!"
