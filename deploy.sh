#!/usr/bin/env bash
#
# Deploy stock-control ke Hostinger.
#
#   ./deploy.sh          -> tanya konfirmasi dulu
#   ./deploy.sh -y       -> langsung jalan tanpa tanya
#
# Jalankan dari Git Bash di folder project. Butuh deploy.config
# (salin dari deploy.config.example).
#
set -euo pipefail

cd "$(dirname "$0")"
[ -f deploy.config ] || { echo "deploy.config tidak ada. Salin dari deploy.config.example."; exit 1; }
# shellcheck disable=SC1091
source deploy.config

SSH="ssh -o BatchMode=yes -p $SSH_PORT $SSH_USER@$SSH_HOST"
STAMP=$(date +%Y%m%d-%H%M%S)
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

say() { printf '\n\033[1m== %s\033[0m\n' "$1"; }
die() { printf '\n\033[31mGAGAL: %s\033[0m\n' "$1" >&2; exit 1; }

# ---------------------------------------------------------------- 1. cek lokal
say "1/8  Memeriksa kondisi lokal"
[ "$(git branch --show-current)" = "main" ] || die "harus di branch main, sekarang di $(git branch --show-current)"
[ -z "$(git status --porcelain)" ] || die "masih ada perubahan belum di-commit. Commit atau stash dulu."
git pull --ff-only origin main
echo "   main di $(git rev-parse --short HEAD)"

# --------------------------------------------------------- 2. cek koneksi & beda
say "2/8  Menghubungi server"
$SSH "cd $APP_PATH && echo '   terhubung, PHP '\$(php -r 'echo PHP_VERSION;')" || die "tidak bisa SSH ke server"

LOCK_LOKAL=$(git hash-object composer.lock)
LOCK_SERVER=$($SSH "cd $APP_PATH && git hash-object composer.lock 2>/dev/null || md5sum composer.lock | cut -d' ' -f1" 2>/dev/null || echo "")
PERLU_COMPOSER=no
[ "$LOCK_LOKAL" != "$LOCK_SERVER" ] && PERLU_COMPOSER=yes
echo "   composer install diperlukan: $PERLU_COMPOSER"

# ------------------------------------------------------------- 3. konfirmasi
if [ "${1:-}" != "-y" ]; then
  say "Siap deploy ke $LIVE_URL"
  read -r -p "   Lanjutkan? ketik ya: " j
  [ "$j" = "ya" ] || { echo "   dibatalkan"; exit 0; }
fi

# ---------------------------------------------------------------- 4. backup
say "3/8  Backup database dan file di server"
$SSH bash -s <<EOF || die "backup gagal - deploy dihentikan"
set -e
cd $APP_PATH
mkdir -p $BACKUP_PATH
DB=\$(grep '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"'"')
US=\$(grep '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"'"')
PW=\$(grep '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'"'"')
mysqldump -u"\$US" -p"\$PW" "\$DB" > $BACKUP_PATH/db-$STAMP.sql
tail -1 $BACKUP_PATH/db-$STAMP.sql | grep -q 'Dump completed' || { echo 'dump tidak selesai'; exit 1; }
echo "   db-$STAMP.sql (\$(du -h $BACKUP_PATH/db-$STAMP.sql | cut -f1))"
tar czf $BACKUP_PATH/app-$STAMP.tar.gz --exclude=vendor -C $APP_PATH .
echo "   app-$STAMP.tar.gz (\$(du -h $BACKUP_PATH/app-$STAMP.tar.gz | cut -f1))"
EOF

# ---------------------------------------------------------------- 5. kirim
say "4/8  Mengirim kode"
git archive --format=tar.gz -o "$TMP/rilis.tar.gz" HEAD
tar tzf "$TMP/rilis.tar.gz" | grep -qE '^\.env$|^vendor/' && die "arsip memuat .env atau vendor - dibatalkan"
scp -q -o BatchMode=yes -P "$SSH_PORT" "$TMP/rilis.tar.gz" "$SSH_USER@$SSH_HOST:/tmp/rilis-$STAMP.tar.gz"
echo "   $(( $(stat -c%s "$TMP/rilis.tar.gz") / 1024 )) KB terkirim"

# ---------------------------------------------------------------- 6. pasang
say "5/8  Memasang di server"
$SSH bash -s <<EOF || die "pemasangan gagal"
set -e
cd $APP_PATH
SEBELUM=\$(md5sum .env | cut -d' ' -f1)
tar xzf /tmp/rilis-$STAMP.tar.gz -C $APP_PATH
rm -f /tmp/rilis-$STAMP.tar.gz
SESUDAH=\$(md5sum .env | cut -d' ' -f1)
[ "\$SEBELUM" = "\$SESUDAH" ] || { echo '.env berubah - ini tidak seharusnya terjadi'; exit 1; }
echo "   .env aman (checksum sama)"
EOF

if [ "$PERLU_COMPOSER" = "yes" ]; then
  say "6/8  composer install (composer.lock berubah)"
  $SSH "cd $APP_PATH && composer install $COMPOSER_FLAGS 2>&1 | tail -5"
else
  say "6/8  composer install dilewati (dependency tidak berubah)"
fi

# ---------------------------------------------------------------- 7. migrasi
say "7/8  Migrasi database dan bersihkan cache"
$SSH bash -s <<EOF || die "migrasi gagal - restore dari $BACKUP_PATH/db-$STAMP.sql"
set -e
cd $APP_PATH
php artisan migrate --force 2>&1 | tail -8
php artisan config:clear >/dev/null 2>&1
php artisan route:clear  >/dev/null 2>&1
php artisan view:clear   >/dev/null 2>&1
echo "   cache dibersihkan"
EOF
# CATATAN: db:seed sengaja TIDAK dijalankan - akan menggandakan data.

# ---------------------------------------------------------------- 8. verifikasi
say "8/8  Verifikasi"
GAGAL=0
for u in /login.php /app.php /api/health; do
  KODE=$(curl -s -o /dev/null -w '%{http_code}' --max-time 25 "$LIVE_URL$u" || echo 000)
  printf '   %-14s %s\n' "$u" "$KODE"
  [ "$KODE" = "200" ] || GAGAL=1
done
$SSH "cd $APP_PATH && php artisan tinker --execute=\"echo '   parts='.App\\Models\\Part::count().' transaksi='.App\\Models\\Transaction::count().' users='.App\\Models\\User::count();\" 2>/dev/null | tail -1"

if [ "$GAGAL" = "1" ]; then
  printf '\n\033[31mAda halaman yang tidak merespons 200. Cek log:\033[0m\n'
  echo "   $SSH 'tail -30 $APP_PATH/storage/logs/laravel.log'"
  echo "   Rollback: tar xzf $BACKUP_PATH/app-$STAMP.tar.gz -C $APP_PATH"
  exit 1
fi

printf '\n\033[32mDeploy selesai.\033[0m Backup: %s (db-%s.sql, app-%s.tar.gz)\n' "$BACKUP_PATH" "$STAMP" "$STAMP"
