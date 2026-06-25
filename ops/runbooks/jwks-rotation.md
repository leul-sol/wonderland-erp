# JWKS / JWT key rotation (S1)

Wonderland S1 signs access tokens with **RS256**. Verifiers (S2, S3, S4, portal BFF) call `GET /s1/api/v1/auth/jwks` or verify via `POST /auth/verify`.

## Key material

| Setting | Default | Purpose |
|---------|---------|---------|
| `JWT_PRIVATE_KEY_PATH` | `storage/secrets/jwt_private.pem` | Signing key (S1 only) |
| `JWT_PUBLIC_KEY_PATH` | `storage/secrets/jwt_public.pem` | Verification / JWKS |
| `JWT_KID` | `wonderland-s1-primary` | Key ID in JWT header and JWKS |

Keys are created automatically on first token issue if missing (`JwtService::ensureKeysExist()`).

## Rotation procedure (zero-downtime window)

Plan a **15-minute maintenance window** if you cannot run two public keys in JWKS yet. S1 currently publishes a single key in JWKS; full dual-key JWKS is a future enhancement.

### 1. Generate new RSA pair (on S1 host)

```bash
docker compose exec s1-identity sh -c '
  cd /var/www/html/storage/secrets
  openssl genrsa -out jwt_private_next.pem 2048
  openssl rsa -in jwt_private_next.pem -pubout -out jwt_public_next.pem
'
```

### 2. Stage the new key with a new `kid`

Update `s1-identity-access/.env` (or secrets mount):

```env
JWT_KID=wonderland-s1-2026-06
JWT_PRIVATE_KEY_PATH=/var/www/html/storage/secrets/jwt_private_next.pem
JWT_PUBLIC_KEY_PATH=/var/www/html/storage/secrets/jwt_public_next.pem
```

### 3. Restart S1 and workers

```powershell
docker compose restart s1-identity s1-workers
```

New logins receive tokens signed with the new key. Existing access tokens remain valid until expiry (`JWT_TTL`, default 60 minutes).

### 4. Force session refresh (optional)

For immediate cutover, super admins can **force logout** affected users from the portal (revokes refresh tokens). Staff re-login with the new key.

### 5. Retire old key material

After `JWT_REFRESH_TTL` (default 30 days) and all access tokens expired:

```bash
docker compose exec s1-identity sh -c '
  cd /var/www/html/storage/secrets
  mv jwt_private.pem jwt_private_retired.pem
  mv jwt_public.pem jwt_public_retired.pem
  mv jwt_private_next.pem jwt_private.pem
  mv jwt_public_next.pem jwt_public.pem
'
```

Remove retired files from backups and secret stores.

### 6. Verify

```bash
curl -s http://localhost/s1/api/v1/auth/jwks | jq .
docker compose exec s2-workforce php artisan test --filter=Jwt
```

Confirm `kid` in JWKS matches `JWT_KID` and S2/S3/S4 can still verify tokens.

## Rollback

If verifiers fail after rotation:

1. Restore previous `JWT_*` paths and `JWT_KID` in `.env`
2. `docker compose restart s1-identity s1-workers`
3. Users with new-key tokens must log in again after rollback

## Audit

Record rotation in the change log. Optional: add an S1 audit entry `jwt.keys_rotated` via super-admin API in a future release.

## References

- S1 SDD §5 — JWT claims and JWKS
- `s1-identity-access/config/jwt.php`
- `ops/runbooks/incident-response.md` — token / auth outages
