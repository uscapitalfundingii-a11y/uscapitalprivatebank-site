#!/usr/bin/env bash
set -euo pipefail

CRM_ROOT="/home/dh_9a4ezr/uscapitalprivatebank.com/crm"
ZIP_PATH="/home/dh_9a4ezr/crm_update_341.zip"
STAMP="$(date +%Y%m%d_%H%M%S)"
TMP_DIR="/home/dh_9a4ezr/perfex_update_341_${STAMP}"
BACKUP_DIR="/home/dh_9a4ezr/crm_pre_update_341_${STAMP}"

if [ ! -f "$ZIP_PATH" ]; then
  echo "zip_missing:$ZIP_PATH"
  exit 1
fi

mkdir -p "$TMP_DIR" "$BACKUP_DIR/application/config"

cp -a "$CRM_ROOT/application/config/app-config.php" "$BACKUP_DIR/application/config/app-config.php"
cp -a "$CRM_ROOT/.htaccess" "$BACKUP_DIR/.htaccess" 2>/dev/null || true
cp -a "$CRM_ROOT/modules" "$BACKUP_DIR/modules"
cp -a "$CRM_ROOT/uploads" "$BACKUP_DIR/uploads" 2>/dev/null || true
cp -a "$CRM_ROOT/media" "$BACKUP_DIR/media" 2>/dev/null || true

unzip -oq "$ZIP_PATH" -d "$TMP_DIR"

SRC_DIR="$TMP_DIR/perfex_crm"
if [ ! -d "$SRC_DIR" ]; then
  echo "source_missing:$SRC_DIR"
  exit 1
fi

cp -a "$SRC_DIR/application/." "$CRM_ROOT/application/"
cp -a "$SRC_DIR/assets/." "$CRM_ROOT/assets/"
cp -a "$SRC_DIR/system/." "$CRM_ROOT/system/"
cp -a "$SRC_DIR/resources/." "$CRM_ROOT/resources/" 2>/dev/null || true
cp -a "$SRC_DIR/install/." "$CRM_ROOT/install/"
cp -a "$SRC_DIR/modules/." "$CRM_ROOT/modules/"

for file in index.php pipe.php package.json tailwind.config.js web.config webpack.mix.js; do
  if [ -f "$SRC_DIR/$file" ]; then
    cp -a "$SRC_DIR/$file" "$CRM_ROOT/$file"
  fi
done

cp -a "$BACKUP_DIR/application/config/app-config.php" "$CRM_ROOT/application/config/app-config.php"
if [ -f "$BACKUP_DIR/.htaccess" ]; then
  cp -a "$BACKUP_DIR/.htaccess" "$CRM_ROOT/.htaccess"
fi

php -r "require '$CRM_ROOT/application/config/migration.php'; echo \$config['migration_version'];"
echo
echo "backup_dir=$BACKUP_DIR"
echo "tmp_dir=$TMP_DIR"
