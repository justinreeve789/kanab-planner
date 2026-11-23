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
