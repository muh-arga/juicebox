# Juicebox Laravel Code Test

A Laravel 13 / PHP 8.3 REST API with MySQL, Sanctum bearer tokens, post ownership, queued welcome emails, and cached current weather for Perth, Australia.

## Requirements

- PHP 8.3 or later, with the extensions required by Laravel and PHPUnit, including PDO MySQL, mbstring, XML/DOM, cURL, OpenSSL, and fileinfo.
- Composer 2.
- A running MySQL 8+ server.

This is an API-only Laravel application. No Node build is needed. Run all commands below from the project root after cloning or downloading the repository.

## Installation

Install the locked dependencies and create your local environment file:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

On Windows, copy `.env.example` to `.env` using your terminal's copy command or file manager.

Configure `.env` to match your MySQL installation:

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juicebox
DB_USERNAME=juicebox_app
DB_PASSWORD=replace-with-a-local-password

QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
```

Create the tables and start Laravel's development server:

```bash
php artisan migrate
php artisan serve
```

The application is available at `http://127.0.0.1:8000`; API endpoints use the `/api` prefix. Import the [Postman collection](docs/postman/Juicebox.postman_collection.json) to explore the API; setup instructions are below.

Optionally, run `php artisan db:seed` once on a fresh development database. It creates `demo@example.com` with password `password`, plus sample users and posts. You can also register your own account through `POST /api/register`.

## Queue and scheduler

Keep the development server running, then open two more terminals in the project root. Start the queue worker in one:

```bash
php artisan queue:work --tries=3 --timeout=30
```

Start the scheduler in the other:

```bash
php artisan schedule:work
```

Registration dispatches `SendWelcomeEmail` after its database transaction commits. The job sends a Mailable; the default log mailer writes the message to `storage/logs/laravel.log` without contacting an email recipient. For SMTP, configure `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, and sender settings in `.env` according to your mail provider.

Manually queue an email for a user ID:

```bash
php artisan app:send-welcome-email 1
php artisan queue:work --stop-when-empty --tries=3 --timeout=30
```

An unknown user exits with a failure status. Email jobs retry up to three times with 30/120-second backoff; weather jobs use 60/300 seconds. As with ordinary queued SMTP delivery, a retry after an uncertain send can duplicate delivery.

```bash
php artisan queue:failed
php artisan queue:retry <failed-job-uuid>
php artisan schedule:list
```

`RefreshWeather` is scheduled hourly and uses a unique job lock to prevent duplicate pending refreshes. Both the scheduler and queue worker must run. For a deployed environment, use a supervised worker and execute `php artisan schedule:run` every minute; `schedule:work` is convenient locally.

## Weather integration

[Open-Meteo](https://open-meteo.com/en/docs) supplies current weather. Its public endpoint needs no API key for this non-commercial code test; review the provider's terms before production/commercial use. Weather data is attributed to Open-Meteo in each response.

| Variable | Default |
| --- | --- |
| `WEATHER_BASE_URL` | `https://api.open-meteo.com` |
| `WEATHER_LOCATION` | `Perth, Australia` |
| `WEATHER_LATITUDE` | `-31.9523` |
| `WEATHER_LONGITUDE` | `115.8613` |
| `WEATHER_TIMEZONE` | `Australia/Perth` |
| `WEATHER_CACHE_TTL` | `900` seconds |
| `WEATHER_TIMEOUT` | `5` seconds per attempt |

The default location is Perth, as required by the test. `WEATHER_LOCATION` sets the response label; it does not geocode a city name. To configure another city, update the label, latitude, longitude, and timezone together. The endpoint serves one configured city. After changing environment settings, clear cached configuration with `php artisan config:clear` and restart queue workers with `php artisan queue:restart`.

```text
WeatherController / RefreshWeather job
                  |
            WeatherService        (15-minute cache and refresh)
                  |
           OpenMeteoService        (HTTP request, validation, mapping)
                  |
         Laravel HTTP client
```

There is no generic external API wrapper: future providers get their own integration service using Laravel's HTTP client. This keeps authentication, retries, and response formats provider-specific.

The endpoint fetches on a cache miss. The hourly job refreshes the same cache proactively; it does not extend cache lifetime beyond 15 minutes. Only valid responses are cached. GET requests retry once for connection errors or provider 5xx responses. Invalid payloads, outdated observations (over two hours old), and provider errors yield a generic `503` response. An unsuccessful background refresh preserves any still-unexpired cache but never extends it. No stale fallback is returned.

## Tests

Create a separate test database and grant your local database account access, using a MySQL administrator:

If you use a different MySQL account, adjust the grant accordingly. Configure the test environment:

```bash
cp .env.testing.example .env.testing
php artisan key:generate --env=testing
```

Set `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_USERNAME`, and `DB_PASSWORD` in `.env.testing` to match your local MySQL installation. Keep `DB_DATABASE=juicebox_testing`. PHPUnit forces MySQL and `juicebox_testing`; the test suite migrates and resets **that database**, so reserve it exclusively for tests. Tests use array cache/mail and faked external HTTP requests; they send no email and call no live weather service.

```bash
composer test
```

Tests cover authentication and token revocation, ownership, validation, Unicode post content, pagination, weather mapping/caching/expiry/failures, welcome mail, and manual job dispatch.

## API documentation (Postman)

Import these two files into Postman:

- [Juicebox API collection](docs/postman/Juicebox.postman_collection.json)
- [Local environment](docs/postman/Juicebox.local.postman_environment.json)

1. Select **Juicebox — Local** as the environment.
2. Set `base_url` to your Laravel server, normally `http://127.0.0.1:8000`, without `/api` or a trailing slash.
3. Set `name`, `email`, and `password`. Use **Authentication → Register** with an unused email, or **Login** with an existing account. For seeded data, use `demo@example.com` / `password` with Login.
4. The collection saves `token` and `user_id` automatically. Protected requests inherit bearer authentication; no manual token copying is needed.
5. Run **Posts → Create post** before Get, Update, or Delete. It saves `post_id` automatically. Delete removes that post and clears the ID.
6. Run **Session → Logout** last to revoke the current token and clear it locally.

Every endpoint includes a description, example responses, and success checks. List requests expose `page` and `per_page`. Weather uses the location configured in Laravel and includes a `503` error example.

For the Collection Runner, requests are ordered from authentication through logout. Use a new email for each full run, or skip Register and log in to an existing account. A full run creates an account and a temporary post, deletes the post, and revokes the last token. Registration and login each issue a token; logout only revokes the current one. Live weather checks require Open-Meteo to be available. Keep populated credentials and tokens out of exported files committed to Git.

## API assumptions

- Registration and login are public; all other API endpoints require a Sanctum bearer token.
- Authenticated users may read posts and minimal user profiles. Only post authors may edit/delete their posts.
- `GET /api/users` is added because the brief requests pagination for users although its endpoint list omits this route.
- User resources expose only ID/name; email, password hashes, and remember tokens are never included.
- Pagination defaults to 15, accepts 1–100 records, and returns standard Laravel links/meta.
- Authentication endpoints share a 10-requests/minute/IP limit. Protected endpoints allow 60 requests/minute/user.
- No UI, roles, password reset, deployment, or GitHub publishing is included. Commit `composer.lock` for reproducible installation; never commit environment secrets.
