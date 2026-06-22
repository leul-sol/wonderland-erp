# S3 — Hospitality Operations

Hotel operations service (rooms, reservations, folios) with journal posting to S4.

## Dev

```powershell
docker compose up -d s3-hospitality s3-workers
docker compose exec s3-hospitality php artisan app:ensure-seeded
```

Health: `http://localhost/s3/api/v1/health`

## Golden path

1. Create reservation → check in (assigns room, opens folio)
2. Post folio charge → S4 journal (DR 1100 / CR 4001)
3. Settle folio → S4 journal (DR 1001 / CR 1100)
4. Check out

## Gateway

`http://localhost/s3/api/v1/...`
