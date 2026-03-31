---
name: ppa-api-script
description: Creates a new PPA CLI test script in `bin/` following the project's XML-RPC call pattern. Includes `get_webhosting_ppa_instance()`, request array, `PPAConnector::checkResponse()`, and error handling. Use when user says 'add bin script', 'new PPA command', 'create CLI tool', or adds files to `bin/`. Do NOT use for modifying `src/Plugin.php` event handlers.
---
# PPA API Script

## Critical

- Every `bin/` script MUST start with `include_once __DIR__.'/../../../../include/functions.inc.php';` — this bootstraps the entire MyAdmin framework including autoloading.
- The PPA method name in the script MUST match a real `pem.*` method from `bin/pleskautomation.methods`. Verify the method exists before writing the script.
- Never call methods with the `pem.` prefix — `PPAConnector` adds it automatically via the XML-RPC2 client's `prefix` option. Call `$ppaConnector->getAccountInfo($request)` not `$ppaConnector->{'pem.getAccountInfo'}($request)`.
- Exception: methods with a dot-separated namespace like `pleskintegration.createWebspace` MUST be called using curly-brace syntax: `$ppaConnector->{'pleskintegration.createWebspace'}($request)` — only the `pem.` prefix is added automatically.
- Always wrap the response check in try/catch with `\Exception` — never let `PPAFailedRequestException` or `PPAMalformedRequestException` go uncaught.

## Instructions

1. **Identify the PPA API method.** Check `bin/pleskautomation.methods` to confirm the method exists in the `pem.*` namespace. Note whether it is a plain method (e.g., `pem.getAccountInfo`) or a namespaced method (e.g., `pem.pleskintegration.createWebspace`).
   - Verify: the method name appears in `bin/pleskautomation.methods` before proceeding.

2. **Create the file at `bin/<name>.php`.** Use camelCase matching the PPA method name (e.g., `bin/getAccountInfo.php`, `bin/disableSubscription.php`). For namespaced methods, see existing files like `bin/createWebspace.php` and `bin/pleskintegration.getAccountWebspaces.php` for naming conventions.
   - Verify: no existing file with the same name in `bin/`.

3. **Write the script body.** Follow this exact structure matching existing scripts like `bin/getAccountInfo.php` and `bin/removeSubscription.php`:

```php
<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
// Build the request array with required parameters
$request = [
    'param_name' => $value
];
$result = $ppaConnector->methodName($request);
echo 'Result:';
var_dump($result);
echo "\n";
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
```

   Key details:
   - `get_webhosting_ppa_instance()` with no args uses `NEW_WEBSITE_PPA_SERVER` constant as default.
   - If the script accepts CLI arguments, read them from `$_SERVER['argv']`: `$id = (int) $_SERVER['argv'][1];`
   - For output, use `var_dump($result)` for mutation scripts (add/remove/update) or `echo preg_replace("/\$\s*array\s+\(/msiU", 'array(', var_export($result, true));` for read/get scripts that return large data.
   - Call `PPAConnector::checkResponse($result)` as a static method (not on the instance).
   - After the try/catch, optionally echo a success message referencing result fields like `$result['result']['account_id']`.
   - Verify: the script follows the bootstrap → connector → request → call → output → checkResponse → success pattern.

4. **Test the script.** Run `php -l bin/<name>.php` to lint, then `php bin/<name>.php [args]` from the project root. The script requires the MyAdmin framework bootstrap, so it must be run in an environment with access to `include/functions.inc.php`.

```bash
php -l bin/<name>.php              # syntax check
php bin/<name>.php [args]          # run the script
```

## Examples

### Example 1: Simple getter with CLI argument

**User says:** "Create a bin script to get account members"

**Actions:**
1. Check `bin/pleskautomation.methods` — confirms `pem.getAccountMembers` exists
2. Check existing `bin/getAccountMembers.php` — file already exists, so review it or pick a different script
3. If creating new, write following the pattern in `bin/getAccountInfo.php`:

```php
<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
$accountId = (int) $_SERVER['argv'][1];
$request = [
    'account_id' => $accountId
];
$result = $ppaConnector->getAccountMembers($request);
echo preg_replace("/\$\s*array\s+\(/msiU", 'array(', var_export($result, true));
echo "\n";
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
```

4. Lint: `php -l bin/getAccountMembers.php`

**Result:** `bin/getAccountMembers.php` — run with `php bin/getAccountMembers.php 127`

### Example 2: Mutation with hardcoded test values

**User says:** "Add a script to disable an account"

**Actions:**
1. Check `bin/pleskautomation.methods` — confirms `pem.disableAccount` exists
2. Check no existing `bin/disableAccount.php` in `bin/`
3. Create `bin/disableAccount.php` following the pattern in `bin/disableSubscription.php`:

```php
<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
$accountId = 127;
$request = [
    'account_id' => $accountId
];
$result = $ppaConnector->disableAccount($request);
echo 'Result:';
var_dump($result);
echo "\n";
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
```

### Example 3: Namespaced method (pleskintegration)

**User says:** "Create a script to remove a webspace"

**Actions:**
1. Check methods list — confirms `pem.pleskintegration.removeWebspace` exists
2. Create `bin/pleskintegration.removeWebspace.php` following the naming pattern of `bin/pleskintegration.getAccountWebspaces.php`:

```php
<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
$webspaceId = (int) $_SERVER['argv'][1];
$request = [
    'webspace_id' => $webspaceId
];
$result = $ppaConnector->{'pleskintegration.removeWebspace'}($request);
echo 'Result:';
var_dump($result);
echo "\n";
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
```

Note the curly-brace syntax `->{'pleskintegration.removeWebspace'}()` because the method name contains a dot.

## Common Issues

**Error: `Call to undefined function get_webhosting_ppa_instance()`**
1. The bootstrap include path is wrong. Verify the script is at `bin/<name>.php` and the include is `__DIR__.'/../../../../include/functions.inc.php'`.
2. The function file `src/get_webhosting_ppa_instance.php` must be autoloaded. Check `composer.json` has the `files` autoload entry for it.

**Error: `PPAMalformedRequestException: Malformed answer from POA`**
1. The API returned a response without a `status` key. This usually means the method name is wrong or the request parameters are malformed.
2. Verify the method name matches exactly what's in `bin/pleskautomation.methods` (case-sensitive).
3. Check that namespaced methods use curly-brace syntax: `$ppaConnector->{'pleskintegration.methodName'}($request)`.

**Error: `PPAFailedRequestException` with an error message**
1. The API call reached PPA but failed. Read `$response['error_message']` for details.
2. Common causes: invalid IDs, missing required request fields, account/subscription doesn't exist.

**Error: `XML_RPC2` connection failures or timeouts**
1. Check the PPA server is reachable on port 8440: `curl -k https://<ip>:8440/RPC2`
2. Verify credentials in the server data — `get_webhosting_ppa_instance()` reads from `get_service_master()`.

**Script runs but produces no output**
1. Ensure `echo` statements are present after the API call. The pattern requires explicit output of `$result`.
2. Check PHP error log — the script may be dying silently on the `include_once` line if the framework isn't available.

**Confusion about `pem.` prefix**
- `PPAConnector::getInstance()` creates an XML-RPC2 client with `'prefix' => 'pem.'`. This means calling `$ppaConnector->getAccountInfo()` actually sends `pem.getAccountInfo` over XML-RPC.
- For `pem.pleskintegration.*` methods, the `pem.` is still auto-prefixed, so call `->{'pleskintegration.createWebspace'}()` which becomes `pem.pleskintegration.createWebspace`.
- Never manually add `pem.` to method calls.