# Postman — Wonderland ERP

## Import

1. Open Postman → **Import**
2. Select both files:
   - `Wonderland-S1-Identity.postman_collection.json`
   - `Wonderland-S1-Local.postman_environment.json`
3. Choose environment **Wonderland — Local (Docker)** (top-right dropdown)

Additional collections (use S1 Login first for `accessToken`):

| Collection | Base URL |
|------------|----------|
| `Wonderland-S2-Workforce.postman_collection.json` | `http://localhost/s2/api/v1` |
| `Wonderland-S3-Hospitality.postman_collection.json` | `http://localhost/s3/api/v1` |
| `Wonderland-S4-Finance.postman_collection.json` | `http://localhost/s4/api/v1` |

## Quick start

1. Ensure stack is running: `.\scripts\start.ps1`
2. Run **Auth → Login** — saves `accessToken` and `refreshToken` automatically
3. Call any other request (Bearer auth uses saved token)

## Default dev credentials

| Variable | Value |
|----------|-------|
| `baseUrl` | `http://localhost/s1/api/v1` |
| `username` | `super.admin` |
| `password` | `ChangeMeNow!10` |
| `serviceKey` | `dev-internal-key-change-in-prod` |

**If login says "Invalid credentials":**
1. Run `docker compose exec s1-identity php artisan app:ensure-seeded`
2. If you used **Change Password**, update the `password` variable in Postman (default new password in that request is `NewSecurePass!10`)

After **List Roles**, set `roleId` to a role you want (e.g. `receptionist` = id from response).
