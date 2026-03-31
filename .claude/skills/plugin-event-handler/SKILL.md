---
name: plugin-event-handler
description: Adds a new event handler method to `src/Plugin.php` following the existing pattern: category check with `get_service_define('WEB_PPA')`, PPA connector calls, `myadmin_log()`, `$event->stopPropagation()`, and `$event['success']` assignment. Use when user says 'add hook', 'new event handler', 'add plugin method'. Do NOT use for CLI scripts or test files.
---
# Plugin Event Handler

## Critical

- Every event handler MUST begin with `if ($event['category'] == get_service_define('WEB_PPA'))` and end with `$event->stopPropagation()` inside that block.
- Every handler MUST be a `public static` method accepting a single `GenericEvent $event` parameter.
- Every handler MUST be registered in `getHooks()` using the `self::$module.'.eventname' => [__CLASS__, 'getMethodName']` format.
- PPA API calls MUST be wrapped in try/catch with `PPAConnector::checkResponse($result)` — never skip response validation.
- Always use `myadmin_log(self::$module, ...)` with `__LINE__, __FILE__, self::$module, $serviceClass->getId()` — never omit the service ID.
- Set `$event['success'] = true` or `$event['success'] = false` to signal outcome to the framework.
- After adding a handler, update `tests/PluginTest.php` — add the method to `eventHandlerProvider()` and update `testGetHooksCount()`.

## Instructions

### Step 1: Determine the event name and method name

Follow the existing naming convention in `src/Plugin.php`:
- Event name: `webhosting.<action>` (e.g., `webhosting.upgrade`, `webhosting.suspend`)
- Method name: `get<Action>` with PascalCase (e.g., `getUpgrade`, `getSuspend`)

Verify the event name is not already registered in `getHooks()` before proceeding.

### Step 2: Register the hook in `getHooks()`

Add a new entry to the return array in `src/Plugin.php` `getHooks()` method:

```php
public static function getHooks()
{
    return [
        self::$module.'.settings' => [__CLASS__, 'getSettings'],
        self::$module.'.activate' => [__CLASS__, 'getActivate'],
        self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
        self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
        self::$module.'.terminate' => [__CLASS__, 'getTerminate'],
        self::$module.'.newaction' => [__CLASS__, 'getNewAction'],  // <-- add here
        'function.requirements' => [__CLASS__, 'getRequirements']
    ];
}
```

Keep `'function.requirements'` as the last entry. Verify the hook key and method name are consistent.

### Step 3: Write the handler method

Add the method to `src/Plugin.php` following this exact skeleton derived from `getDeactivate()` and `getReactivate()`:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 * @throws \Detain\MyAdminPleskAutomation\PPAFailedRequestException
 * @throws \Detain\MyAdminPleskAutomation\PPAMalformedRequestException
 */
public static function getNewAction(GenericEvent $event)
{
    if ($event['category'] == get_service_define('WEB_PPA')) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', 'PleskAutomation NewAction', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        if (count($extra) == 0) {
            $msg = 'Blank/Empty Plesk Subscription Info';
            myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event['success'] = false;
        } else {
            $subscriptoinId = $extra[2];
            include_once __DIR__.'/get_webhosting_ppa_instance.php';
            $ppaConnector = get_webhosting_ppa_instance($serverdata);
            $request = ['subscription_id' => $subscriptoinId];
            $result = $ppaConnector->ppaMethodName($request);
            try {
                PPAConnector::checkResponse($result);
                $event['success'] = true;
            } catch (PPAFailedRequestException $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'ppaMethodName Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            } catch (\Exception $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'ppaMethodName Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            }
            myadmin_log(self::$module, 'info', 'ppaMethodName Called got '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        }
        $event->stopPropagation();
    }
}
```

Key elements to customize:
- Replace `getNewAction` with your actual method name
- Replace `'PleskAutomation NewAction'` with a descriptive log message
- Replace `ppaMethodName` with the actual PPA API method (e.g., `upgradeSubscription`, `enableSubscription`)
- Replace `$request` contents with the parameters required by the PPA API method
- If the handler modifies `$extra`, persist it to DB using this pattern from `getActivate()`:

```php
$db = get_module_db(self::$module);
$settings = get_module_settings(self::$module);
$serExtra = $db->real_escape(myadmin_stringify($extra));
$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_extra='{$serExtra}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
```

Verify `$event->stopPropagation()` is called inside the category check block before proceeding.

### Step 4: Update `tests/PluginTest.php`

Three changes are required:

**4a.** Add the new method to `eventHandlerProvider()`:

```php
public function eventHandlerProvider(): array
{
    return [
        'getActivate' => ['getActivate'],
        'getReactivate' => ['getReactivate'],
        'getDeactivate' => ['getDeactivate'],
        'getTerminate' => ['getTerminate'],
        'getChangeIp' => ['getChangeIp'],
        'getMenu' => ['getMenu'],
        'getRequirements' => ['getRequirements'],
        'getSettings' => ['getSettings'],
        'getNewAction' => ['getNewAction'],  // <-- add here
    ];
}
```

This automatically covers `testEventHandlerSignature` and `testEventHandlerTypeHint` via `@dataProvider`.

**4b.** If the hook was added to `getHooks()`, update `testGetHooksCount()`:

```php
public function testGetHooksCount(): void
{
    $this->assertCount(7, Plugin::getHooks());  // was 6
}
```

**4c.** Add the new event key to `testGetHooksContainsExpectedKeys()`:

```php
$expectedKeys = [
    'webhosting.settings',
    'webhosting.activate',
    'webhosting.reactivate',
    'webhosting.deactivate',
    'webhosting.terminate',
    'webhosting.newaction',  // <-- add here
    'function.requirements',
];
```

Verify tests pass:

```bash
vendor/bin/phpunit tests/PluginTest.php
```

### Step 5: Run tests

Execute the full test suite to confirm nothing is broken:

```bash
vendor/bin/phpunit
```

Verify all tests pass, specifically `testGetHooksCount`, `testGetHooksContainsExpectedKeys`, `testEventHandlerSignature`, and `testEventHandlerTypeHint` with the new method.

## Examples

### Example: Add an "upgrade" event handler

**User says:** "Add a handler for upgrading a PPA subscription to a new service template."

**Actions taken:**

1. Register in `getHooks()`: `self::$module.'.upgrade' => [__CLASS__, 'getUpgrade']`

2. Add method to `src/Plugin.php`:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 * @throws \Detain\MyAdminPleskAutomation\PPAFailedRequestException
 * @throws \Detain\MyAdminPleskAutomation\PPAMalformedRequestException
 */
public static function getUpgrade(GenericEvent $event)
{
    if ($event['category'] == get_service_define('WEB_PPA')) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', 'PleskAutomation Upgrade', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        if (count($extra) == 0) {
            $msg = 'Blank/Empty Plesk Subscription Info, cannot upgrade';
            myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event['success'] = false;
        } else {
            $subscriptoinId = $extra[2];
            include_once __DIR__.'/get_webhosting_ppa_instance.php';
            $ppaConnector = get_webhosting_ppa_instance($serverdata);
            $request = [
                'subscription_id' => $subscriptoinId,
                'service_template_id' => $event['newTemplateId']
            ];
            $result = $ppaConnector->upgradeSubscription($request);
            try {
                PPAConnector::checkResponse($result);
                $event['success'] = true;
            } catch (PPAFailedRequestException $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'upgradeSubscription Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            } catch (\Exception $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'upgradeSubscription Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            }
            myadmin_log(self::$module, 'info', 'upgradeSubscription Called got '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        }
        $event->stopPropagation();
    }
}
```

3. Update `tests/PluginTest.php`:
   - Add `'getUpgrade' => ['getUpgrade']` to `eventHandlerProvider()`
   - Change `assertCount(6, ...)` to `assertCount(7, ...)` in `testGetHooksCount()`
   - Add `'webhosting.upgrade'` to `$expectedKeys` in `testGetHooksContainsExpectedKeys()`

4. Run tests:

```bash
vendor/bin/phpunit tests/PluginTest.php
```

All tests pass.

**Result:** New `getUpgrade` handler registered, implemented following existing patterns, tests updated and passing.

## Common Issues

### `$event['success']` not being set

Every code path inside the category check must set `$event['success']` to `true` or `false`. The framework relies on this to determine whether the operation succeeded. If you forget, the calling code may behave unpredictably. Check both the success and error branches.

### `$event->stopPropagation()` in wrong location

It MUST be inside the `if ($event['category'] == get_service_define('WEB_PPA'))` block but OUTSIDE any inner if/else. Place it as the last statement before the closing brace of the category check. If placed outside, it will stop propagation for all categories, breaking other plugins.

### `testGetHooksCount` fails with "Failed asserting that 7 matches expected 6"

You added a hook to `getHooks()` but forgot to update the count in `tests/PluginTest.php:testGetHooksCount()`. Change the `assertCount` argument to match the new total.

### `testGetHooksContainsExpectedKeys` fails with "Hook key 'webhosting.newaction' should be registered"

You added the key to the test's `$expectedKeys` array but forgot to add the corresponding entry in `Plugin::getHooks()`. Verify the hook key string matches exactly (including the `self::$module.'.'` prefix which resolves to `'webhosting.'`).

### `testEventHandlerSignature` fails for the new method

The method signature must be exactly: `public static function getMethodName(GenericEvent $event)`. Common mistakes:
- Missing `static` keyword
- Parameter not named `$event`
- Missing `GenericEvent` type hint
- Extra parameters

### `PPAConnector::checkResponse` throws `PPAMalformedRequestException: Malformed answer from POA`

The PPA API response is missing the `status` key. This usually means the API method name is wrong or the request parameters are malformed. Check `bin/` scripts for the correct method name and parameter structure — each script demonstrates a specific PPA API call.

### `$extra` array is empty

The service has no stored PPA IDs yet (typical for new or failed activations). Always check `count($extra) == 0` before accessing `$extra[0..3]`. The indices are: `[0]` = accountId, `[1]` = userId/memberId, `[2]` = subscriptionId, `[3]` = webspaceId.

### Handler not firing at all

Two common causes:
1. The hook key in `getHooks()` doesn't match the event being dispatched. Verify the event name is correct.
2. The `get_service_define('WEB_PPA')` check doesn't match the service category. Verify the service is configured as a PPA webhosting service in the database.