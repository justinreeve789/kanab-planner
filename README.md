# Python virtual environment
In WSL, set up Python3 virtual environment

`python3 -m venv myenv`

`source myenv/bin/activate`

# Install dotenv
`pip3 install dotenv`

# Install populartimes

`pip install --upgrade git+https://github.com/m-wrzr/populartimes`

# Download Chrome Drivers
cd drivers
wget https://storage.googleapis.com/chrome-for-testing-public/135.0.7049.95/linux64/chromedriver-linux64.zip
unzip chromedriver-linux64.zip
mv chromedriver-linux64/chromedriver ./
rm -fr chromedriver-linux64
rm chrome-linux64.zip
chmod a+x chrome-linux

# Running with Docker

The quickest way to run everything (the app, a queue worker, the scheduler, MySQL, Redis, Meilisearch, a Valhalla
routing server and Mailpit):

```sh
docker compose up -d --build
```

Then open http://localhost:8080. Nothing needs configuring first. On its first start the `app` container:

1. generates an `APP_KEY` and keeps it in the `storage` volume (set `APP_KEY` yourself to manage it);
2. runs the migrations and, because the database is empty, loads `database/dumps` (`SEED_DATABASE=auto`);
3. builds the search indexes (`php artisan search:reindex`);
4. caches config and routes, then starts Apache. The `worker` (Horizon) and `scheduler` containers wait for it.

The `valhalla` container downloads the Utah and Arizona OpenStreetMap extracts from Geofabrik and builds its routing
tiles the first time (roughly 10–30 minutes and a few GB of disk). Until it's ready, drive times are estimated. Reset
emails go to Mailpit at http://localhost:8025 until you configure real SMTP.

`/up` is a health check (it also checks the database). Logs go to `docker compose logs`.

## Configuring it

Compose reads a `.env` file next to `docker-compose.yml`. Anything not set there uses the defaults in the compose
file. **For anything public, set at least these:**

| Variable | Why |
| --- | --- |
| `APP_URL` | The public address, used in emails and Google sign-in (default `http://localhost:8080`). |
| `APP_PORT` | The host port (default 8080). Put a TLS-terminating proxy (Caddy, nginx, a load balancer) in front. |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `MEILISEARCH_KEY` | Replace the `change-me-...` defaults. |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` | A real SMTP server for password resets. |
| `UDOT_API_KEY`, `AZ511_API_KEY` | Free keys from [UDOT Traffic](https://udottraffic.utah.gov/developers/doc) and [AZ511](https://az511.gov/help/endpoint/event) for closures and roadwork on drives. |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Google sign-in; register `APP_URL/auth/google/callback` as a redirect URI. |
| `OPENAI_API_KEY`, `GEMINI_API_KEY`, `DO_*` | The assistant, and photos from DigitalOcean Spaces. |

Other knobs: `ROUTING_DRIVER=estimate` (skip Valhalla, e.g. on a small server; you can then remove that service),
`VALHALLA_TILE_URLS` (other OpenStreetMap extracts), `SEED_DATABASE` (`auto`, `always` or `never`).

Useful commands:

```sh
docker compose exec app php artisan search:reindex        # rebuild the search indexes
docker compose exec app php artisan update:road-events    # fetch closures now
docker compose exec app php artisan update:data --trails --restaurants
docker compose run --rm app php artisan tinker
```

To update: `git pull && docker compose up -d --build`. Migrations run on start.

The image doesn't include the data-gathering tools (Chrome for the spiders, Tesseract and Poppler for OCR, Python
for popular times); run those commands from a development checkout.

## Drive times and road conditions

Drive times come from [Valhalla](https://github.com/valhalla/valhalla), an open-source router, using
OpenStreetMap roads (`ROUTING_DRIVER=valhalla`, `VALHALLA_URL`). Answers are cached for 30 days. If Valhalla is
unreachable, or can't place a point on a road, that drive is estimated from straight-line distance instead.

`php artisan update:road-events` (every ten minutes, from the scheduler) reads closures, roadwork and incidents from
the UDOT and AZ511 feeds. Full closures on a route are routed around; anything else on a route adds a delay and a note.
Without keys the command does nothing. None of the free sources offers live traffic speeds for these rural roads, so
drive times are free-flow times plus those adjustments.

# Setting up the app

```sh
composer install
npm ci
cp .env.example .env && php artisan key:generate   # then set DB_*, MEILISEARCH_*, GOOGLE_* and OPENAI_* values
php artisan migrate --seed       # schema, reference rows, and the dataset from database/dumps (DumpSeeder)
npx mix                          # build the Vue app into public/js and public/css
npm run docs:build               # build the user guide into public/docs, served at /docs
```

`php artisan update:data --trails --restaurants --events` recalculates the adventurousness / solitariness /
strenuousness scores the itinerary builder matches against. Run it after importing or editing trails, restaurants
or events.

Google sign-in needs `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`, with `APP_URL/auth/google/callback` registered
as an authorized redirect URI in Google Cloud (override it with `GOOGLE_REDIRECT_URI`). Search and the assistant need Meilisearch; the assistant also needs OpenAI.

# User guide

The guide in `docs/` is an [Astro Starlight](https://starlight.astro.build) site. `npm run docs:build` writes it to
`public/docs` (not committed), which the web server serves as static files; `DocsController` covers directory URLs
and setups where every request reaches Laravel.

```sh
npm run docs:dev                       # live preview at http://localhost:4321/docs
cd docs && npx playwright install chromium && npm run screenshots   # retake screenshots from a running app (APP_URL)
```

Pages live in `docs/src/content/docs`, and the sidebar is in `docs/astro.config.mjs`. When a screen in the app
changes, update the matching page and retake the screenshots.

# Running the tests

The suite needs a MySQL/MariaDB server (the app uses MySQL-only SQL such as `RAND()`, `DATE_FORMAT()` and
`POW()`). Defaults live in `phpunit.xml` and can be overridden from the shell.

```sh
# One-time: a throwaway schema for the suite, plus a copy of the real dataset for the Data suite.
mysql -uroot -e "CREATE DATABASE kanab_test; CREATE DATABASE kanab_data;
  CREATE USER 'kanab'@'%' IDENTIFIED BY 'kanab'; GRANT ALL ON kanab_test.* TO 'kanab'@'%'; GRANT ALL ON kanab_data.* TO 'kanab'@'%';"
tests/Data/load-dumps.sh            # migrate kanab_data and seed it from database/dumps

vendor/bin/phpunit                  # Unit + Feature + Data
vendor/bin/phpunit --testsuite Unit # no database needed
DB_HOST=mysql vendor/bin/phpunit    # e.g. inside Sail/Docker
vendor/bin/phpunit --group known-issues   # documented bugs that still fail (excluded by default)
```

* **Unit** – pure logic (dates/seasons, trail timing, preferences, intent detection). No database.
* **Feature** – HTTP endpoints and end-to-end itinerary generation. `RefreshTestDatabase` migrates `kanab_test`
  (schema plus reference rows only; the dataset lives in seeders) and adds a small deterministic fixture set (`tests/Support/SeedsKanabFixtures.php`).
  It refuses to run against a schema whose name doesn't end in `_test`.
* **Data** – sanity checks against the real dataset in `kanab_data`, inside a rolled-back transaction. Skipped if
  that schema isn't loaded.

The Vue code has its own Vitest suite (`tests/js`): pure helpers in `resources/js/lib`, the Pinia stores and a few
components.

```sh
npm test
```
