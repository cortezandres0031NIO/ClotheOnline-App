# Mi armario

A private, single-owner wardrobe application in Spanish for iPhone and Mac. Manage garment photos, reusable outfits, purchases, wear history and statistics, with full data-and-photo backups.

This repository preserves the existing application design and functionality. It contains source code, app icons, setup examples, deployment documentation and tests. It does **not** contain personal records, photographs, passwords, databases or the local PHP binary.

For everyday use, see [the Spanish user guide](LEEME.md). For hosting, see [the cPanel installation guide](INSTALACION-CPANEL.md).

## Application

- Garments: photo, category, color, notes and optional purchase details; search, edit and archive.
- Outfits: reusable combinations with optional outfit photos.
- Wear records: independent garment snapshots, so editing an outfit does not rewrite history.
- Statistics: usage counts, never-worn items, time since last use, historical cost per wear and purchases grouped by currency.
- Private access: one owner account, authenticated image delivery, CSRF protection and revision checks for conflicting edits.
- ZIP backup export and validated restoration, including photos.
- PWA manifest and icons. The hosted application requires internet; there is no offline data storage or synchronization.

## Stack

PHP 8.2+ with PDO, GD (JPEG/PNG/WebP), mbstring, EXIF, ZIP, fileinfo and sessions. Local development uses PDO SQLite; hosting uses PDO MySQL. PHP 8.4 is the documented deployment target. The frontend is plain JavaScript and CSS, with no build step, npm packages or external frontend services. Composer is not required.

PHP creates the database table through `bin/install.php`; no separate schema import or migration download is needed. Production configuration is provided in `app/config.example.php`.

## Run a fresh clone locally

Install PHP with the extensions above first. The Git repository does not ship a platform-specific PHP executable. The existing working folder may still contain its optional ignored runtime; [runtime/ORIGEN.md](runtime/ORIGEN.md) records its source.

On a Mac, you can double-click **Abrir-armario.command** after PHP is available. Alternatively, from the repository directory:

```sh
php bin/local-config.php
php bin/install.php --local
php -d upload_max_filesize=128M -d post_max_size=132M -d memory_limit=512M -d max_execution_time=120 -S 127.0.0.1:8765 -t public bin/router.php
```

Open **http://127.0.0.1:8765**. On first use, choose the password for the single owner account. Keep the server terminal open and press Control+C there to stop it.

The local setup creates ignored `app/config.php` and `private-local/`. It does not overwrite an existing configuration. Do not run local setup against a production configuration. No example data is loaded into your wardrobe.

## Tests

Tests additionally require Node.js 18+ and Python 3.10+ with Pillow. These tools are **not** required to run the application. For example:

```sh
python3 -m venv .venv
.venv/bin/python -m pip install -r requirements-dev.txt
PYTHON_BIN="$PWD/.venv/bin/python" sh scripts/check.sh
```

The runner checks PHP/JavaScript syntax and runs the existing HTTP integration, image processing and statistics tests. It uses its own temporary configuration, data and server on port 8767. `PHP_BIN`, `PYTHON_BIN`, `NODE_BIN` and `TEST_PORT` can override executable paths or the test port. Reports remain in the temporary directory printed by the runner for inspection.

The test password in `tests/integration.py` belongs only to its isolated disposable test installation. It is not a default application password. The image fixtures are created by the tests; no personal photos are required.

See [PRUEBAS.md](PRUEBAS.md) for previous test coverage and remaining real-device/hosting checks. Tests are local; no GitHub Actions workflow or automatic deployment is enabled.

## Deploy

Follow [INSTALACION-CPANEL.md](INSTALACION-CPANEL.md) to configure PHP/MySQL, create the owner account and enable HTTPS. Only `public/` is the web document root. Keep `app/`, configuration and stored photos outside the public directory.

GitHub stores and versions the source. **GitHub Pages cannot run this PHP/MySQL application.** Pushing this repository does not publish the app or move your wardrobe data. Transfer personal data separately using the app's authenticated backup and restore features.

The old user guide refers to a separately prepared hosting ZIP. That generated archive is deliberately not versioned here; all of its application source is in this repository.

## Repository layout

```text
app/                 Private PHP application code and configuration example
bin/                 Installation, configuration, checks and maintenance
public/              Web entry points, JavaScript, CSS and PWA assets
tests/               Isolated integration, statistics and image tests
scripts/check.sh     One-command local verification
runtime/ORIGEN.md    Provenance of the optional, untracked local PHP runtime
docs/                Project specification and GitHub push instructions
```

`private-local/`, `app/config.php`, backups, credentials and temporary artifacts are ignored. Do not force-add them to Git. A private GitHub repository is recommended, but repository privacy does not replace the application's login or private file storage.

## Push to a new GitHub repository

Follow [docs/GITHUB.md](docs/GITHUB.md). No remote repository or deployment is created by these files.

No open-source license has been selected for the application. Publishing the source does not automatically grant an open-source license. Third-party PHP runtime licensing is documented separately in its provenance file.
