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
