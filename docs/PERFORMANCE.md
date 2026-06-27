# Wonderland ERP — performance notes

## Why pages can feel slow (especially on Windows)

The portal is a **server-rendered Inertia app**. Every sidebar click waits for the server before the next page appears. Measured locally in Docker:

| Step | Typical cost |
|------|----------------|
| Laravel boot (web-portal) | ~3–5s on Windows bind mounts |
| Each backend API (S1/S2/S3/S4) | ~1–3s per service call |
| Login (old) | login API + `/auth/me` + portal boot |
| Sidebar page (old) | portal boot + 2–3 **sequential** API calls |

So **5–25 seconds** matches: slow PHP file I/O + multiple API round-trips + `php artisan serve` handling **one request at a time**.

## What we optimized in code

1. **Login** — S1 login now returns the user profile; portal skips the extra `/auth/me` call.
2. **Parallel API loads** — list pages (employees, users/items, reservations, etc.) fetch data concurrently via `Http::pool`.
3. **Navigation cache** — sidebar/tasks/menu cached in session until permissions change.
4. **Token refresh** — checked once per request, not on every API call.
5. **OPcache** — enabled in PHP Docker images to reduce autoload cost after rebuild.

## What you should do locally (biggest wins)

### 1. Run the repo inside WSL2 (strongly recommended on Windows)

Clone and run Docker from the **Linux filesystem** (`~/projects/wonderland-erp`), not `E:\`. Windows bind mounts to containers are often 5–10× slower.

### 2. Rebuild containers after pulling these changes

```bash
docker compose build web-portal s1-identity
docker compose up -d
```

### 3. Avoid clicking the next sidebar item before the current page finishes

With `php artisan serve`, requests are queued. Wait for the spinner/page load to complete before navigating again.

### 4. Production / staging

- Use **php-fpm + nginx** (or Laravel Octane) with multiple workers — not `artisan serve`.
- Set `APP_DEBUG=false`, run `php artisan config:cache` and `route:cache`.
- Keep Redis for sessions (already configured in `docker-compose.yml`).

## How to measure

From inside the web-portal container:

```bash
curl -s -o /dev/null -w "login:%{time_total}\n" http://localhost:9000/login
```

After signing in, use browser DevTools → Network → click a sidebar link → check **Waiting (TTFB)** on the document request. That is server time; the rest is frontend assets.

## If it is still slow after WSL2 + rebuild

Check backend health:

```bash
docker compose exec web-portal curl -s -o /dev/null -w "s1:%{time_total}\n" http://wh-gateway/s1/api/v1/health
docker compose exec web-portal curl -s -o /dev/null -w "s2:%{time_total}\n" http://wh-gateway/s2/api/v1/health
```

If each health check is >1s, investigate MySQL/Redis and container resources (CPU/RAM).
