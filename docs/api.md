# REST API v1

Base URL: `/api/v1/`

## Authentication and transport rules
- Always use HTTPS.
- Send `license_key` only in JSON body for `POST` requests or in the `X-License-Key` header.
- Never put the license key in the URL or query string.
- Recommended headers: `Accept: application/json`, `Content-Type: application/json`.
- Rate limiting: HTTP `429` with error code `rate_limited`.

## Stable error payload
```json
{
  "success": false,
  "error": {
    "code": "license_expired",
    "message": "The license has expired."
  }
}
```

Supported error codes: `invalid_license_key`, `license_not_found`, `license_expired`, `license_revoked`, `license_suspended`, `activation_limit_reached`, `domain_not_allowed`, `product_mismatch`, `updates_not_allowed`, `invalid_request`, `rate_limited`, `invalid_download_token`.

## Endpoints
- `POST /api/v1/licenses/activate`
- `POST /api/v1/licenses/deactivate`
- `POST /api/v1/licenses/check`
- `POST /api/v1/licenses/heartbeat`
- `POST /api/v1/updates/check`
- `GET /api/v1/updates/download`
- `GET /api/v1/products/{product_slug}/latest`

Requests and example responses are documented in the source responses and README. The activation endpoints return license status, expiry dates, activation counters and grace period. The update endpoint returns changelog, version requirements, checksum and a temporary `download_url`.
