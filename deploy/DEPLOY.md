# PPRC Deployment Guide

Target host: `41.72.157.26` (Ubuntu 22.04.5 LTS, Docker 29.x, Compose v5)
Install path: `/opt/pprc`
App port (host): `8093` — reverse-proxied by Nginx Proxy Manager to `pretoriaprc.co.za`.
Repo: <https://github.com/vassago85/PPRC>

Containers that will run:

| Name             | Role                          | Host-exposed |
| ---------------- | ----------------------------- | ------------ |
| `pprc-app`       | nginx + php-fpm               | `8093/tcp`   |
| `pprc-queue`     | queue worker                  | —            |
| `pprc-scheduler` | scheduler loop                | —            |
| `pprc-pgsql`     | PostgreSQL 16                 | —            |
| `pprc-redis`     | Redis 7                       | —            |
| `pprc-minio`     | MinIO object store            | —            |
| `pprc-minio-init`| One-shot bucket bootstrap     | —            |

`pprc-app`'s bundled nginx also proxies `/media/*` to `pprc-minio:9000/*` on the internal Docker network, so all public assets (Exco photos, gallery images, hero images) are served through the same domain and port. Sensitive files (EFT proofs) get random/hashed filenames via Livewire's default upload behaviour, so path guessing is not viable.

---

## 1. DNS

Add **one** A record at your DNS provider:

```
pretoriaprc.co.za   A   41.72.157.26
```

Wait ~5–10 min for propagation, then verify:

```bash
dig +short pretoriaprc.co.za
# should print 41.72.157.26
```

## 2. Clone the repo

```bash
sudo mkdir -p /opt/pprc
sudo chown -R "$USER":"$USER" /opt/pprc
git clone https://github.com/vassago85/PPRC.git /opt/pprc
cd /opt/pprc
```

## 3. Create production `.env`

```bash
cp .env.production.example .env
```

Edit `.env` and set the following — do **not** keep defaults for anything marked "change-me":

```bash
nano .env
```

Required edits:

- `APP_KEY` — leave blank for now; we'll generate it after the image builds (step 5).
- `APP_URL` — `https://pretoriaprc.co.za`
- `DB_PASSWORD` — long random password.
- `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` — long random values; these same values go into `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`.
- `AWS_ACCESS_KEY_ID` = `MINIO_ROOT_USER`
- `AWS_SECRET_ACCESS_KEY` = `MINIO_ROOT_PASSWORD`
- `MAILGUN_DOMAIN` / `MAILGUN_SECRET` — **optional.** You can now also set these later from **Admin → Site settings → Email (Mailgun)**. Values entered in the admin UI override `.env`.
- `PAYSTACK_PUBLIC_KEY` / `PAYSTACK_SECRET_KEY` / `PAYSTACK_WEBHOOK_SECRET` — **optional.** Also editable from **Admin → Site settings → Payments (Paystack)**.

> **Runtime-editable settings.** Mailgun credentials, S3/MinIO credentials, Paystack keys, EFT bank details and contact info are all editable at runtime via **Admin → Site settings** (`/admin/settings`). Values there take precedence over `.env` without a redeploy. The `.env` values above act as bootstrap defaults only.

Quick way to generate strong values:

```bash
openssl rand -base64 32   # for DB_PASSWORD
openssl rand -hex 20      # for MINIO_ROOT_USER
openssl rand -base64 48   # for MINIO_ROOT_PASSWORD
```

## 4. Build images

```bash
cd /opt/pprc
docker compose -f docker-compose.prod.yml build
```

The first build takes ~5–8 minutes (composer install + npm build + PHP extensions). Subsequent builds use layer cache.

## 5. Generate `APP_KEY`

```bash
docker compose -f docker-compose.prod.yml run --rm app artisan key:generate --show
```

Copy the `base64:...` string it prints into `.env` as `APP_KEY=base64:...`, then save.

## 6. Start the stack

```bash
docker compose -f docker-compose.prod.yml up -d
```

Watch it come up:

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f app
```

Expected:

- `pprc-pgsql`, `pprc-redis`, `pprc-minio` go `healthy`.
- `pprc-minio-init` runs once, creates the `pprc-media` bucket with anonymous-download policy, then exits `0`.
- `pprc-app` entrypoint waits for pgsql + redis, runs migrations, caches config/routes/views, then starts nginx + php-fpm.
- `pprc-queue` and `pprc-scheduler` start after `pprc-app`.

Smoke-test locally on the server:

```bash
curl -I http://127.0.0.1:8093/up
# expect: HTTP/1.1 200 OK
```

## 7. Seed demo data (first deploy only)

```bash
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

This creates:

- Roles + permissions.
- Default membership types (full, associate, spouse, junior, honorary).
- Demo admin users: `chair@pprc.local`, `treasurer@pprc.local`, `secretary@pprc.local`, `membership@pprc.local`, `admin@pprc.local` — all with password `password`. **Change these immediately** in the admin UI.
- Default homepage sections, About page, Exco page placeholders, FAQ entries, and contact/bank site settings.

## 8. Configure Nginx Proxy Manager

In your NPM dashboard, add a new **Proxy Host**:

- **Domain name:** `pretoriaprc.co.za`
- **Scheme:** `http`
- **Forward hostname / IP:** `41.72.157.26` (or the Docker host IP NPM uses for your other apps)
- **Forward port:** `8093`
- **Block common exploits:** on
- **Websockets support:** on
- **Cache assets:** off (Laravel handles caching; leaving this off avoids stale `/up` and admin responses)

Under **SSL**:

- **SSL certificate:** Request a new SSL certificate (Let's Encrypt).
- **Force SSL:** on
- **HTTP/2:** on
- **HSTS:** on (only once you're confident SSL is stable)

Save. NPM will issue the cert.

Verify:

```
https://pretoriaprc.co.za/          -> public homepage
https://pretoriaprc.co.za/admin     -> Filament admin login
https://pretoriaprc.co.za/portal/membership -> member portal (after login)
https://pretoriaprc.co.za/up        -> "Application up" health endpoint
```

## 9. Post-deploy checks

```bash
docker compose -f docker-compose.prod.yml exec app php artisan about
docker compose -f docker-compose.prod.yml exec app php artisan migrate:status
docker compose -f docker-compose.prod.yml exec app php artisan queue:failed
```

Upload test: log in to `/admin` as `chair@pprc.local`, edit the Exco page, upload a photo for a committee member, save. The image URL should be of the form `https://pretoriaprc.co.za/media/pprc-media/...` and should load in the browser.

## 10. Routine operations

### Deploy an update

```bash
cd /opt/pprc
git pull
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan filament:optimize
```

### View logs

```bash
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f queue
docker compose -f docker-compose.prod.yml logs -f scheduler
```

### Run artisan commands

```bash
docker compose -f docker-compose.prod.yml exec app php artisan <command>
```

### Import legacy members from CSV

```bash
docker cp /path/to/members.csv pprc-app:/tmp/members.csv
docker compose -f docker-compose.prod.yml exec app php artisan members:import /tmp/members.csv
```

### Manually age-out expired sub-members (normally daily at 02:00)

```bash
docker compose -f docker-compose.prod.yml exec app php artisan memberships:age-sub-members
```

### Backup PostgreSQL

```bash
docker compose -f docker-compose.prod.yml exec -T pgsql \
    pg_dump -U pprc -d pprc --clean --if-exists --no-owner \
    > /opt/pprc/backups/pprc-$(date +%F).sql
```

### Backup MinIO data

```bash
tar -czf /opt/pprc/backups/pprc-media-$(date +%F).tar.gz \
    -C /var/lib/docker/volumes/pprc_pprc-minio/_data .
```

## 11. Troubleshooting

| Symptom                                              | Fix                                                                                   |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------- |
| NPM shows 502 Bad Gateway                            | Check `docker compose ps`; ensure `pprc-app` is `healthy`. Check `logs app`.          |
| `Mixed content` warnings in browser                  | Confirm `TRUSTED_PROXIES=*` in `.env` and re-run `php artisan config:cache`.          |
| Uploaded images return 404 at `/media/...`           | Check `pprc-minio-init` logs — bucket policy must be `download`.                      |
| Admin login redirect loop                            | Usually stale cache after deploy. Run `php artisan optimize:clear` then `optimize`.   |
| Queue jobs never run                                 | Check `docker compose logs queue`. Verify `QUEUE_CONNECTION=database` and migrations. |
| Scheduler jobs never run                             | Check `docker compose logs scheduler`. `schedule:work` must be the `pprc-scheduler` CMD. |

## 12. Security reminders

- Rotate all demo user passwords immediately after first login.
- Rotate `MINIO_ROOT_USER`/`MINIO_ROOT_PASSWORD` and `DB_PASSWORD` before opening to real traffic.
- Keep Paystack webhook secret (`PAYSTACK_WEBHOOK_SECRET`) secret; the webhook route verifies the signature.
- Do **not** commit `.env` — only `.env.production.example` is tracked.
- Nightly DB + MinIO backups should be shipped off-box (cron + rclone to B2/S3 recommended; not in scope for this deploy).
