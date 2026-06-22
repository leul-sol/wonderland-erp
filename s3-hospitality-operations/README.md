# S3 — Hospitality Operations

Hotel operations service (rooms, reservations, folios, F&B, inventory) with journal posting to S4.

## Dev

```powershell
docker compose up -d s3-hospitality s3-workers
docker compose exec s3-hospitality php artisan app:ensure-seeded
```

Health: `http://localhost/s3/api/v1/health`

## Golden paths

### Hotel stay
1. Create reservation → check in (assigns room, opens folio)
2. Post folio charge → S4 journal (DR 1100 / CR 4001)
3. Settle folio → S4 journal (DR 1001 / CR 1100)
4. Check out

### F&B + inventory
1. Create purchase order → approve → receive goods
   - Stock increases; S4 journal DR 1200 / CR 2001
2. Create restaurant order (optional guest folio)
3. Add menu items → finalize
   - Revenue: DR 1100 or 1001 / CR 4002
   - COGS: DR 5003 / CR 1200
   - Inventory depletes per recipe

## Gateway

`http://localhost/s3/api/v1/...`
