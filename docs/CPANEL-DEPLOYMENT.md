# Deploy this repository on the qmascore cPanel account

The checked-in `.cpanel.yml` uses cPanel's `deployment.tasks` format. It reads the clone at `/home/qmascore/repositories/ClotheOnline-App` and copies the explicitly listed production files into `/home/qmascore/mi-armario`.

## Locations

```text
/home/qmascore/repositories/ClotheOnline-App/   Git clone; not the live website
/home/qmascore/mi-armario/
  .htaccess                                  Denies access to the project root
  app/
    core.php
    config.example.php                       Placeholder settings, not credentials
    config.php                               Created privately on the server only
  bin/
    install.php
    check.php
    maintenance.php
  public/                                    Subdomain document root
    .htaccess
    index.php
    api.php
    app.js
    app.css
    manifest.webmanifest
    sw.js
    icon.svg
    icon-192.png
    icon-512.png
/home/qmascore/armario-private/                Recommended private storage location
```

Point the subdomain's document root to **`/home/qmascore/mi-armario/public`**. Do not point it at the Git clone, the whole deployed project or `public_html`. The existing PHP include paths work unchanged with this layout.

## First deployment

1. In cPanel, open **Git Version Control → Manage** for `ClotheOnline-App`.
2. Confirm the checked-out branch is **main** and the repository has no local changes.
3. Open **Pull or Deploy** and select **Update from Remote**.
4. Select **Deploy HEAD Commit** and check that cPanel reports success.

Deployment requires `/bin/bash`, `/bin/mkdir`, `/usr/bin/rsync`, and write access to the destination as `qmascore`. The YAML creates the destination if it does not exist. It rejects symbolic links in the selected source paths and deployment targets rather than following them.

This prepares the application files, not the database or login. For the first installation only:

- Create the MySQL database and user.
- Create `/home/qmascore/mi-armario/app/config.php` privately from `config.example.php` **only if it does not already exist**. Do not edit the Git clone to add credentials.
- Set `environment` to `production`, `origin` to the exact HTTPS subdomain, `storage` to `/home/qmascore/armario-private`, and the actual MySQL credentials.
- Use the hosting account's matching PHP version to run:

```sh
cd /home/qmascore/mi-armario
php bin/install.php
php bin/check.php
```

- Configure the subdomain, certificate and HTTPS redirect, and verify login, photos and backups.

The PHP extensions, resource limits and installation requirements are in [INSTALACION-CPANEL.md](../INSTALACION-CPANEL.md). Do not use `--local` on the hosting account. The deployment does not run the installer, change credentials or perform database migrations.

## Subsequent updates

Push updates to GitHub `main`, then repeat **Update from Remote → Deploy HEAD Commit** in cPanel. Existing allowlisted code/assets are updated; identical deployments can be repeated safely.

- `app/config.php` is **never copied, created, overwritten, deleted or chmodded** by deployment, including when it is absent.
- No `.git`, local database, backup, private photo, `private-local`, local PHP runtime, test, README, development script or local configuration is copied.
- File selection is an explicit allowlist, not a broad directory copy. Even untracked sensitive files in the clone are excluded.
- Deployment does not use `--delete`. Other existing destination files and private data remain untouched. It does not remove obsolete production files automatically.
- When a future change introduces a new production file, add its exact path to the allowlist in `.cpanel.yml`. Never add a private configuration or data path.
- File copies are delayed until rsync finishes transferring. This is not a fully atomic release switch or automatic rollback system; check cPanel's deployment result and retry a failed transfer before relying on the update.

The deployment recipe automates file copying when **Deploy HEAD Commit** is selected. A push to GitHub alone does **not** trigger this cPanel pull-deployment workflow. No webhook, scheduler or automatic GitHub-to-cPanel trigger is configured.

## Validation

Twelve local checks passed using macOS rsync and isolated temporary directories: valid YAML/Bash syntax, exactly 16 deployed production files, unchanged source contents, expected permissions, exclusion of credentials/dev files, repeated deployment, checksum-based updates, preserved config/data, missing-file rejection, symlink rejection and byte-for-byte preservation of the original application code.

The deployment task can be tested locally by parsing the YAML and substituting only the two absolute source/destination paths with temporary directories. Check first deployment, repeated deployment, updated assets, preservation of an existing config and private files, absence of sensitive/dev files, and rejection of missing files or symlink targets. Do not run the original production paths on a development machine.

The real cPanel execution, PHP/MySQL environment, subdomain and HTTPS still require verification on the hosting account.

References: [cPanel deployment format](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/) and [pull-deployment steps](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-set-up-deployment/).
