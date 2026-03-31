# MyAdmin PleskAutomation Webhosting Plugin

PHP plugin integrating Parallels Plesk Automation (PPA) for webhosting provisioning via XML-RPC. Part of the MyAdmin billing/hosting platform.

## Commands

```bash
composer install                          # install dependencies
vendor/bin/phpunit                        # run all tests
vendor/bin/phpunit tests/PPAConnectorTest.php  # run single test file
vendor/bin/phpunit --filter testCheckResponse  # run single test method
```

## Architecture

**Namespace**: `Detain\MyAdminPleskAutomation\` → `src/` · **Tests**: `Detain\MyAdminPleskAutomation\Tests\` → `tests/`

**CI & IDE**: `.github/workflows/tests.yml` runs the automated test pipeline · `.idea/` contains JetBrains project config including `inspectionProfiles/profiles_settings.xml`, `deployment.xml`, and `encodings.xml`

**Core classes** (`src/`):
- `PPAConnector.php` — XML-RPC 2 client singleton via `getInstance($ip, $login, $password)`, connects to PPA on port `8440/RPC2` with `pem.` prefix. Static `checkResponse()` validates API responses
- `Plugin.php` — Symfony EventDispatcher hooks for `webhosting.activate`, `webhosting.reactivate`, `webhosting.deactivate`, `webhosting.terminate`, `webhosting.settings`, `function.requirements`
- `PPAFailedRequestException.php` — thrown when PPA returns non-zero status
- `PPAMalformedRequestException.php` — thrown when PPA response missing `status` key
- `PPADomainDoesNotExistException.php` — extends `PPAFailedRequestException`

**Helper functions** (`src/`):
- `get_webhosting_ppa_instance.php` — factory returning `PPAConnector` singleton from server data
- `get_pleskautomation_info_from_domain.php` — reverse-lookup domain → `[$accountId, $memberId, $subscriptionId, $webspaceId]`

**CLI scripts** (`bin/`) — 30+ PPA API test scripts: `addAccount.php`, `addAccountMember.php`, `activateSubscription.php`, `createWebspace.php`, `getSubscription.php`, `getDomainList.php`, `removeSubscription.php`, `upgradeSubscription.php`, etc.

**Tests** (`tests/`):
- `PPAConnectorTest.php` — unit tests for `checkResponse()`, reflection-based structure tests
- `PPAExceptionsTest.php` — exception hierarchy and catchability tests
- `PluginTest.php` — hook registration, static properties, method signatures
- `FileExistenceTest.php` — verifies all `src/` files exist with correct classes/namespaces
- `FunctionFilesTest.php` — verifies helper function file contents

## Key Patterns

**PPA API call pattern** (used in all `bin/` scripts and `src/Plugin.php`):
```php
$ppaConnector = get_webhosting_ppa_instance($serverdata);
$request = ['account_id' => $accountId];
$result = $ppaConnector->methodName($request);
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
```

**Plugin activation flow** in `Plugin::getActivate()`: `addAccount` → `addAccountMember` → `activateSubscription` → `pleskintegration.createWebspace`. Each step stores IDs in `$extra[0..3]` array serialized to DB.

**Event handler pattern** — each handler checks `$event['category'] == get_service_define('WEB_PPA')`, calls `$event->stopPropagation()`, sets `$event['success']`.

**Running tests with PHPUnit**:
```bash
vendor/bin/phpunit --testsuite Unit          # run unit tests only
vendor/bin/phpunit --coverage-text           # run with coverage report
php -l src/Plugin.php                        # lint a single file
```

## Dependencies

- PHP >= 7.4 · `ext-soap` · `symfony/event-dispatcher` ^5/^6/^7 · `XML_RPC2_Client` (PEAR)
- Dev: `phpunit/phpunit` ^9.6
- Config: `phpunit.xml.dist` · `composer.json` · `.scrutinizer.yml` · `.travis.yml` · `.codeclimate.yml`

## Conventions

- Commit messages: lowercase, descriptive
- All PPA methods use `PPAConnector::checkResponse()` for error handling
- `bin/` scripts include `__DIR__.'/../../../../include/functions.inc.php'` (MyAdmin bootstrap)
- Typo `$subscriptoinId` is used consistently throughout codebase — do not "fix" it
- Tests use `ReflectionClass`/`ReflectionProperty` for structure verification and `file_get_contents` for source inspection
- PSR-4 autoloading; helper functions in `src/` are not namespaced classes

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
