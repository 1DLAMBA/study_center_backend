# COE Study Center Backend

Laravel API for the COE Study Center — student applications, personal details, clearances, payments, and file uploads.

## API documentation (Scramble)

This project uses [Scramble](https://scramble.dedoc.co) to auto-generate OpenAPI documentation from controllers, FormRequests, and resources.

| URL | Description |
|-----|-------------|
| `/docs/api` | Interactive API docs UI |
| `/docs/api.json` | OpenAPI 3.1 JSON spec |

By default, docs are available only when `APP_ENV=local`. To expose them in other environments, set `SCRAMBLE_DOCS_ENABLED=true` in `.env`.

Export a static spec file:

```bash
php artisan scramble:export
```

Validate doc generation:

```bash
php artisan scramble:analyze
```
