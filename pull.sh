#!/bin/bash

# ASETUKSET
PROD_HOST="puhde"
PROD_URL="https://puhde.com"
LOCAL_URL="http://localhost:8080"
DB_KONTTI="kasvisto-db-1"
LOCAL_WP_KONTTI="kasvisto-wordpress-1"

# TIETOKANNAN TUNNUKSET (Samat kuin push-skriptissä)
DB_USER="wpuser"
DB_PASS="salasana123"
DB_NAME="wordpress"

AIKALEIMA=$(date +"%Y-%m-%d_%H%M")
TIEDOSTON_NIMI="pull_from_prod_${AIKALEIMA}.sql"

echo "📥 1/4 Luodaan varmuuskopio tuotannossa ($PROD_URL)..."
ssh $PROD_HOST "wp db export /tmp/$TIEDOSTON_NIMI --path=/var/www/html --allow-root"

echo "🚚 2/4 Siirretään tiedosto palvelimelta paikallisesti..."
scp $PROD_HOST:/tmp/$TIEDOSTON_NIMI .
ssh $PROD_HOST "rm /tmp/$TIEDOSTON_NIMI"

echo "🚜 3/4 Tuodaan kanta paikalliseen DB-konttiin..."
# Käytetään suoraan mysql-komentoa kontin sisällä
docker exec -i $DB_KONTTI mysql -u $DB_USER -p$DB_PASS $DB_NAME < $TIEDOSTON_NIMI

echo "🔄 4/4 Päivitetään osoitteet: $PROD_URL -> $LOCAL_URL"
# Osoitteiden muutos pitää tehdä WP-kontin puolella, koska siellä on WP-CLI
docker exec $LOCAL_WP_KONTTI wp search-replace "$PROD_URL" "$LOCAL_URL" --allow-root

echo "✅ Valmis! Paikallinen kehitysympäristö on nyt ajan tasalla."
