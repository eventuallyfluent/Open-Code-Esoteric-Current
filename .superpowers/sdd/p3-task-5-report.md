# Phase 3 Task 5 Report: GitHub Actions Workflows

## Status: Complete

## Files Created

| File | Description |
|------|-------------|
| `worker/.github/workflows/automation.yml` | Manual trigger (`workflow_dispatch`) with `dry_run` boolean input + PR trigger |
| `worker/.github/workflows/schedule.yml` | Daily cron schedule at 06:00 UTC |

## Workflow Details

### automation.yml
- **Triggers:** `workflow_dispatch` (manual with `dry_run` boolean), PR events
- **Runner:** `ubuntu-latest`, Node 20 with npm cache
- **Steps:** `actions/checkout@v4`, `actions/setup-node@v4`, `npm ci --omit=dev`, `npm start`
- **Env vars:** DEEPSEEK_API_KEY, WORDPRESS_URL, WORDPRESS_API_KEY, WORDPRESS_API_SECRET, DRY_RUN
- **Working directory:** `./worker`

### schedule.yml
- **Triggers:** `schedule` with `cron: '0 6 * * *'` (daily at 06:00 UTC)
- **Runner:** `ubuntu-latest`, Node 20 with npm cache
- **Steps:** Same as automation.yml (no dry run input)
- **Env vars:** DEEPSEEK_API_KEY, WORDPRESS_URL, WORDPRESS_API_KEY, WORDPRESS_API_SECRET
- **Working directory:** `./worker`

## Commit

```
80d37a4 feat(worker): GitHub Actions workflows -- manual trigger and daily schedule
```
