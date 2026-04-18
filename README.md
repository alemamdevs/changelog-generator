# Auto Changelog Generator (Laravel)

Production-ready changelog generator that ingests GitHub push webhooks, processes commits asynchronously, categorizes changes using Conventional Commits with AI fallback, and generates structured Markdown changelog releases with semantic versioning.

## Features

- GitHub push webhook ingestion (`/api/github/webhooks/push`)
- HMAC signature verification via `X-Hub-Signature-256`
- Redis queue-first architecture for scalable processing
- Duplicate prevention by repository + commit hash
- Conventional Commit categorization: `feature`, `fix`, `refactor`, `chore`, `docs`, `breaking`
- AI fallback classification for non-conventional commit messages
- Semantic version bump automation (`major`/`minor`/`patch`)
- Markdown changelog file generation and persistence
- Admin monitoring UI for releases and webhook deliveries

## Architecture Summary

- `GitWebhookController` accepts GitHub push payloads and queues processing.
- `ProcessGitHubPushWebhookJob` handles asynchronous processing.
- `WebhookCommitProcessingService` orchestrates dedup, categorization, release creation, and markdown rendering.
- Repository layer handles persistence abstractions.

## Database Schema (Core)

- `webhook_deliveries`: incoming webhook audit log and processing status
- `processed_commits`: deduplication table with unique `(repository_full_name, commit_hash)`
- `releases`: semantic release metadata + markdown output
- `commits`: enriched commit records with category, scope, breaking flag, source
- `changelogs`: structured changelog entries per release

## Local Setup

1. Install dependencies:

```powershell
composer install
npm install
```

2. Configure environment:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

3. Set required `.env` values:

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=changelog_generator
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

GITHUB_WEBHOOK_SECRET=your-strong-secret
GITLAB_WEBHOOK_SECRET=your-strong-secret
CHANGELOG_QUEUE_CONNECTION=redis
CHANGELOG_QUEUE_NAME=changelog
CHANGELOG_DEFAULT_REPOSITORY=owner/repository
```

4. Migrate + start app:

```powershell
php artisan migrate
npm run build
php artisan serve
php artisan queue:work redis --queue=changelog,default --tries=3 --backoff=3
```

## CLI Usage

Generate changelog from local git range (sync):

```powershell
php artisan changelog:generate --repo="C:\path\to\repo" --from="v1.2.0" --to="HEAD" --branch="main"
```

Queue mode:

```powershell
php artisan changelog:generate --repo="C:\path\to\repo" --from="HEAD~50" --to="HEAD" --branch="main" --queue
```

## GitHub Webhook Configuration

1. In GitHub repository settings, go to **Webhooks** → **Add webhook**.
2. Set:
	- **Payload URL**: `https://your-domain.com/api/webhooks/push`
	- **Content type**: `application/json`
	- **Secret**: same value as `GITHUB_WEBHOOK_SECRET`
	- **Events**: select **Just the push event**
3. Ensure your queue worker is running.
4. Push a commit and verify delivery status in:
	- `GET /admin/webhooks`
	- `GET /admin/releases`

Legacy GitHub endpoint (optional): `https://your-domain.com/api/github/webhooks/push`

## GitLab Webhook Configuration

1. In GitLab project settings, go to **Settings → Webhooks**.
2. Set:
	- **URL**: `https://your-domain.com/api/webhooks/push`
	- **Secret token**: same value as `GITLAB_WEBHOOK_SECRET`
	- **Trigger**: enable **Push events**
3. Ensure your queue worker is running.
4. Push a commit and verify delivery status in:
	- `GET /admin/webhooks`
	- `GET /admin/releases`

Legacy GitLab endpoint (optional): `https://your-domain.com/api/gitlab/webhooks/push`

## Testing

Run focused tests:

```powershell
php artisan test --compact --filter=GitHubWebhookControllerTest
php artisan test --compact --filter=WebhookCommitProcessingTest
php artisan test --compact --filter=CommitCategorizerServiceTest
```

## Production Notes

- Use HTTPS for webhook endpoint.
- Keep `GITHUB_WEBHOOK_SECRET` rotated and stored securely.
- Run at least one dedicated queue worker for `changelog` queue.
- Configure process supervision (systemd/Supervisor/Kubernetes) for queue workers.
- Enable centralized logs and failed job monitoring.
