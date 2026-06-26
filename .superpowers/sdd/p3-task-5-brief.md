### Task 5: GitHub Actions workflow

**Files:**
- Create: `C:\Dev\Open Code Esoteric Current\worker\.github\workflows\automation.yml`
- Create: `C:\Dev\Open Code Esoteric Current\worker\.github\workflows\schedule.yml`

- [ ] **Step 1: Create automation.yml (manual + PR trigger)**

```yaml
name: Run Automation Worker

on:
  workflow_dispatch:
    inputs:
      dry_run:
        description: 'Dry run (no API calls)'
        type: boolean
        default: false

jobs:
  run:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: ./worker
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: ./worker/package.json
      - run: npm ci --omit=dev
      - run: npm start
        env:
          DEEPSEEK_API_KEY: ${{ secrets.DEEPSEEK_API_KEY }}
          WORDPRESS_URL: ${{ secrets.WORDPRESS_URL }}
          WORDPRESS_API_KEY: ${{ secrets.WORDPRESS_API_KEY }}
          WORDPRESS_API_SECRET: ${{ secrets.WORDPRESS_API_SECRET }}
          DRY_RUN: ${{ inputs.dry_run || 'false' }}
```

- [ ] **Step 2: Create schedule.yml (daily cron)**

```yaml
name: Scheduled Automation

on:
  schedule:
    # Runs at 06:00 UTC daily
    - cron: '0 6 * * *'

jobs:
  run:
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: ./worker
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: ./worker/package.json
      - run: npm ci --omit=dev
      - run: npm start
        env:
          DEEPSEEK_API_KEY: ${{ secrets.DEEPSEEK_API_KEY }}
          WORDPRESS_URL: ${{ secrets.WORDPRESS_URL }}
          WORDPRESS_API_KEY: ${{ secrets.WORDPRESS_API_KEY }}
          WORDPRESS_API_SECRET: ${{ secrets.WORDPRESS_API_SECRET }}
```

- [ ] **Step 3: Commit**

```bash
git add worker/.github/
git commit -m "feat(worker): GitHub Actions workflows â€” manual trigger and daily schedule"
```
