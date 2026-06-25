# Avalokiteshvara Shrine

A Laravel shrine where visitors can light butter lamps and offer them before Avalokiteshvara. All offerings and names are stored publicly and shared with every visitor.

## Features

- Avalokiteshvara thangka radiating light at the top of the shrine
- Light a butter lamp at the bottom and read the traditional offering verse (Spectral italic)
- Add an optional name to your offering, as in a Tibetan monastery
- Offer the lamp — it rises and is placed before Avalokiteshvara
- All butter lamps and names are public and persisted in the database

## Local development

### Laravel Herd (recommended on macOS)

This project is linked to Herd at **http://GreatCompassionateOne.test**.

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build          # or npm run dev while developing assets
```

If you move or clone the project, link it again:

```bash
herd link
```

### Without Herd

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run dev             # Vite dev server
php artisan serve       # in another terminal
```

Visit `http://127.0.0.1:8000`.

## Deploy to Laravel Cloud

### Option A: Laravel Cloud CLI (fastest)

Install the [Laravel Cloud CLI](https://github.com/laravel/cloud-cli), authenticate, then ship from this directory:

```bash
# One-time setup (clone cloud-cli, add alias, install gh if prompted)
gh repo clone laravel/cloud-cli ~/Developer/cloud-cli
cd ~/Developer/cloud-cli && composer install
echo 'alias cloud="php ~/Developer/cloud-cli/cloud"' >> ~/.zshrc && source ~/.zshrc

cloud auth
git init && git add . && git commit -m "Prepare shrine for Laravel Cloud"
gh repo create GreatCompassionateOne --public --source=. --push
cloud ship
```

During `cloud ship`, attach a **MySQL** or **Postgres** database and set this **deploy command**:

```bash
php artisan migrate --force
```

Laravel Cloud runs `npm ci && npm run build` during the build phase by default.

### Option B: Laravel Cloud dashboard

1. Push this repository to GitHub, GitLab, or Bitbucket.
2. Sign in at [cloud.laravel.com](https://cloud.laravel.com) and create a new application.
3. Connect your repository and select this project.
4. Add a **MySQL** or **Postgres** database to your environment (Laravel Cloud sets `DB_*` variables automatically).
5. Set these **Deploy Commands** on your environment:

   ```bash
   php artisan migrate --force
   ```

6. Ensure build commands include asset compilation (Laravel Cloud defaults usually run `npm ci && npm run build`).
7. Deploy. Your shrine will be live at your Laravel Cloud URL.

### Environment notes

- `APP_KEY` is generated automatically on Laravel Cloud.
- Use the attached database; SQLite is only for local development.
- No queues, Redis, or file storage are required for this app.

## Tech stack

- Laravel 13
- Tailwind CSS 4
- Vite
- Spectral font (italic verse typography)
