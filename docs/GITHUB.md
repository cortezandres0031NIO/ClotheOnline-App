# Put this project on GitHub

These steps upload the application source, not your wardrobe records, photos or passwords. Keep personal data in the running app and use its backup feature to transfer it to your eventual hosting.

## 1. Create an empty repository

In your GitHub account, create a new repository, for example **mi-armario**, and select **Private**. Leave the options to add a README, .gitignore and license unchecked: the project already includes its own files, and a license has not been selected.

Copy the repository's HTTPS address. It will look like:

```text
https://github.com/YOUR-ACCOUNT/mi-armario.git
```

Do not put a password or access token in that address.

## 2. Open this project's folder in Terminal

The repository root is the **mi-armario** folder containing `README.md`, `app`, `bin` and `public`. It is not the parent Codex conversation folder or its `work` directory.

Open Terminal in that folder, or type `cd `, drag the folder into Terminal, and press Return. Check the repository contents:

```sh
git status --short
git diff --cached --stat
```

The prepared files are staged for the first commit unless an initial commit has already been created. Personal settings and data must not appear in the list. Never use `git add -f` to include ignored private files.

## 3. Create the first commit if it does not exist yet

Git needs an author name and email. Use your own details; a GitHub-provided `noreply` email can keep your personal email out of commit history. These settings apply only to this repository:

```sh
git config user.name "YOUR NAME"
git config user.email "YOUR GITHUB EMAIL OR NOREPLY ADDRESS"
git add .
git commit -m "Import existing Mi armario application"
```

Skip this step if the first commit is already present. You can inspect it with `git log -1 --oneline`.

## 4. Connect and push

Replace the example URL with the exact address you copied:

```sh
git remote add origin https://github.com/YOUR-ACCOUNT/mi-armario.git
git push -u origin main
```

Use GitHub's credential manager/browser authentication when prompted. Do not paste account passwords or tokens into project files. If `origin` already exists, inspect `git remote -v` instead of adding a second one. Confirm the destination before changing it.

Refresh the repository page on GitHub. It should show the README and source directories, and should not contain `private-local`, `app/config.php`, personal photos, databases or backups.

## What happens next

The application remains running in the same local folder; Git initialization does not move or erase your data. A fresh clone starts with an empty wardrobe and requires local setup or cPanel installation. Pushing code does not deploy the site or synchronize the local and hosted databases.

If Git is missing from Terminal, your programmer can help install/configure it or use a Git client to open this existing repository.
