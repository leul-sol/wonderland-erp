# TLS at the gateway (SEC-002)

Local dev uses **HTTP on port 80** (default `docker compose up`).

Staging and shared hosts must terminate TLS at `wh-gateway`.

## 1. Generate certificates

**Self-signed (staging / lab):**

```powershell
.\scripts\generate-tls-certs.ps1
```

Creates `gateway/certs/fullchain.pem` and `gateway/certs/privkey.pem` (git-ignored).

**Production:** replace those files with certificates from your CA (Let's Encrypt, internal PKI). Keep the same filenames or update `gateway/nginx.tls.conf`.

## 2. Start with TLS

```powershell
docker compose -f docker-compose.yml -f docker-compose.tls.yml up -d
```

| Port | Behaviour |
|------|-----------|
| 80 | Redirects to HTTPS (except `/health` for load balancers) |
| 443 | Portal + `/s1`–`/s4` APIs |

Portal `APP_URL` is set to `https://localhost` via the TLS compose override.

## 3. Trust self-signed cert (browser)

Import `gateway/certs/fullchain.pem` into your OS trust store, or accept the browser warning in lab environments only.

## 4. Production checklist

- [ ] Real CA-issued cert, not self-signed
- [ ] HSTS header (add in nginx when on HTTPS-only production)
- [ ] Firewall: expose 443 only; restrict 80 to LB health checks
- [ ] Rotate cert before expiry; document owner in hotel ops calendar
