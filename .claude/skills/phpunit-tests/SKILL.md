---
name: phpunit-tests
description: Writes PHPUnit 9 test classes in `tests/` using the project's patterns: `ReflectionClass` for structure tests, `file_get_contents` for source inspection, `PPAConnector::checkResponse()` behavioral tests. Namespace `Detain\MyAdminPleskAutomation\Tests\`. Use when user says 'add tests', 'write test', 'test coverage'. Do NOT use for non-test PHP files.
---
# PHPUnit Tests

## Critical

- **Namespace**: Every test class MUST use `namespace Detain\MyAdminPleskAutomation\Tests;`
- **strict_types**: Every test file MUST start with `declare(strict_types=1);` after the `<?php` tag
- **Extends**: All test classes extend `PHPUnit\Framework\TestCase` — nothing else
- **No mocks of external services**: This project does NOT mock XML-RPC connections or DB calls. Instead, use `ReflectionClass` for structure validation and `file_get_contents()` for source code inspection
- **No execution of DB-dependent code**: Methods that touch MyAdmin globals (`get_module_db`, `get_service`, etc.) are tested via static analysis of their source, never by calling them
- **PHPUnit version**: 9.6 — do NOT use PHPUnit 10+ attributes (`#[Test]`, `#[DataProvider]`). Use `@dataProvider` annotations

## Instructions

### Step 1: Create the test file

Place the file in `tests/` with the naming convention `<ClassName>Test.php`. Match the class being tested. For example, tests for `src/PPAConnector.php` go in `tests/PPAConnectorTest.php`.

Verify: The file path is `tests/<ClassName>Test.php` and does not duplicate an existing test file. Check existing tests: `tests/PPAConnectorTest.php`, `tests/PPAExceptionsTest.php`, `tests/PluginTest.php`, `tests/FileExistenceTest.php`, `tests/FunctionFilesTest.php`.

### Step 2: Write the file header

Every test file follows this exact boilerplate, matching the pattern in `tests/PPAConnectorTest.php`:

```php
<?php

declare(strict_types=1);

namespace Detain\MyAdminPleskAutomation\Tests;

use Detain\MyAdminPleskAutomation\ClassName;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the ClassName class.
 *
 * One-line description of test coverage scope.
 */
class ClassNameTest extends TestCase
{
```

Verify: `namespace` is exactly `Detain\MyAdminPleskAutomation\Tests`, `declare(strict_types=1)` is present, class extends `TestCase`.

### Step 3: Write class structure tests

Always start with existence and reflectability tests. These are the foundation — every test file in this project has them (see `tests/PPAConnectorTest.php` and `tests/PPAExceptionsTest.php`):

```php
public function testClassExists(): void
{
    $this->assertTrue(class_exists(ClassName::class));
}

public function testIsInstantiable(): void
{
    $ref = new ReflectionClass(ClassName::class);
    $this->assertTrue($ref->isInstantiable());
}

public function testNamespace(): void
{
    $ref = new ReflectionClass(ClassName::class);
    $this->assertSame('Detain\\MyAdminPleskAutomation', $ref->getNamespaceName());
}
```

Verify: Tests pass with `vendor/bin/phpunit tests/<ClassName>Test.php`.

### Step 4: Write method existence and signature tests using Reflection

For each public method, verify it exists, its visibility, whether it's static, and its parameter count:

```php
public function testMethodNameExists(): void
{
    $ref = new ReflectionClass(ClassName::class);
    $this->assertTrue($ref->hasMethod('methodName'));
    $this->assertTrue($ref->getMethod('methodName')->isPublic());
    $this->assertTrue($ref->getMethod('methodName')->isStatic());
}

public function testMethodNameParameterCount(): void
{
    $ref = new ReflectionClass(ClassName::class);
    $method = $ref->getMethod('methodName');
    $this->assertCount(2, $method->getParameters());
}
```

Verify: Each method on the class under test has at least an existence test.

### Step 5: Write property tests using Reflection

For static properties, verify existence, visibility, and values:

```php
public function testPropertyExists(): void
{
    $ref = new ReflectionClass(ClassName::class);
    $this->assertTrue($ref->hasProperty('propName'));
    $prop = $ref->getProperty('propName');
    $this->assertTrue($prop->isStatic());
    $this->assertTrue($prop->isPublic());
}

public function testPropertyValue(): void
{
    $this->assertSame('expected', ClassName::$propName);
}
```

Verify: All public static properties are covered.

### Step 6: Write behavioral tests for pure methods

Only call methods that have no side effects (no DB, no network). `PPAConnector::checkResponse()` is the canonical example (tested in `tests/PPAConnectorTest.php`):

```php
public function testCheckResponseReturnsTrueOnSuccess(): void
{
    $result = PPAConnector::checkResponse(['status' => 0]);
    $this->assertTrue($result);
}

public function testCheckResponseThrowsFailedOnNonZeroStatus(): void
{
    $this->expectException(PPAFailedRequestException::class);
    $this->expectExceptionMessage('Something went wrong');
    PPAConnector::checkResponse(['status' => 1, 'error_message' => 'Something went wrong']);
}
```

Verify: Tests exercise both success and error paths of the pure method.

### Step 7: Write source inspection tests for DB-dependent methods

When a method depends on MyAdmin globals, validate its behavior by reading the source file with `file_get_contents()` and asserting expected strings (see `tests/FunctionFilesTest.php` for examples):

```php
public function testGetActivateReferencesExpectedApiCalls(): void
{
    $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
    $this->assertStringContainsString('addAccount', $source);
    $this->assertStringContainsString('addAccountMember', $source);
}
```

Use `__DIR__ . '/../src/'` to build paths. Never hardcode absolute paths.

Verify: Each DB-dependent method has at least one source inspection test covering its key operations.

### Step 8: Write exception hierarchy tests

For exception classes, test existence, inheritance chain, throwability, message preservation, and cross-type catchability (see `tests/PPAExceptionsTest.php` for the established pattern):

```php
public function testExceptionExtendsBase(): void
{
    $ref = new ReflectionClass(CustomException::class);
    $this->assertTrue($ref->isSubclassOf(\Exception::class));
}

public function testExceptionIsThrowable(): void
{
    $this->expectException(CustomException::class);
    throw new CustomException('test');
}

public function testExceptionMessage(): void
{
    $e = new CustomException('my message');
    $this->assertSame('my message', $e->getMessage());
}

public function testExceptionCode(): void
{
    $e = new CustomException('fail', 42);
    $this->assertSame(42, $e->getCode());
}

public function testExceptionPrevious(): void
{
    $prev = new \RuntimeException('root cause');
    $e = new CustomException('wrapped', 0, $prev);
    $this->assertSame($prev, $e->getPrevious());
}
```

Verify: Inheritance is tested both directly (`isSubclassOf`) and via catch blocks.

### Step 9: Write data provider tests for repetitive checks

Use `@dataProvider` annotation (NOT PHP 8 attributes) when testing multiple similar items (see `tests/PluginTest.php` and `tests/FileExistenceTest.php` for examples):

```php
/**
 * @dataProvider sourceFileProvider
 */
public function testSourceFileExists(string $relativePath): void
{
    $fullPath = dirname(__DIR__) . '/src/' . $relativePath;
    $this->assertFileExists($fullPath);
}

public function sourceFileProvider(): array
{
    return [
        'PPAConnector.php' => ['PPAConnector.php'],
        'Plugin.php' => ['Plugin.php'],
    ];
}
```

Data provider methods are `public`, return `array`, and are NOT annotated with `@test`.

Verify: Data provider method name matches the `@dataProvider` annotation exactly.

### Step 10: Use setUp() for state reset between tests

When testing singletons or static state, reset via Reflection in `setUp()` (see `tests/PPAConnectorTest.php` for the pattern):

```php
protected function setUp(): void
{
    $ref = new ReflectionProperty(PPAConnector::class, 'xmlrpcProxy');
    $ref->setAccessible(true);
    $ref->setValue(null, null);
}
```

Verify: Tests pass when run individually AND as a suite (no test ordering dependencies).

### Step 11: Run the tests

```bash
vendor/bin/phpunit tests/<ClassName>Test.php    # run single test file
vendor/bin/phpunit                               # run full suite
vendor/bin/phpunit --filter testMethodName       # run single test method
```

All tests must pass. If a test fails, fix it before moving on.

Verify: test run exits with code 0.

## Examples

**User says**: "Add tests for the helper function file `src/get_webhosting_ppa_instance.php`"

**Actions taken**:
1. Check existing tests — `tests/FunctionFilesTest.php` already covers helper function files, so add tests there or create a dedicated file
2. Create `tests/GetWebhostingPpaInstanceTest.php` if a separate file is warranted
3. Add boilerplate with `declare(strict_types=1)`, namespace, TestCase import
4. Write `testFileExists` using `assertFileExists(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php')`
5. Write `testStartsWithPhpTag` using `file_get_contents` + `assertStringStartsWith('<?php', ...)`
6. Write `testContainsFunctionDeclaration` asserting `'function get_webhosting_ppa_instance'` in source
7. Write `testHasServerParameter` asserting `'$server'` in source
8. Write `testDefaultsToFalse` asserting `'$server = false'` in source
9. Write `testCallsGetInstance` asserting `'PPAConnector::getInstance'` in source
10. Run:

```bash
vendor/bin/phpunit tests/GetWebhostingPpaInstanceTest.php
```

**Result**: 6 tests, 6 assertions, all green.

**User says**: "Write tests for a new exception class `src/PPATimeoutException.php` that extends `PPAFailedRequestException`"

**Actions taken**:
1. Verify `src/PPATimeoutException.php` exists before writing tests
2. Create `tests/PPATimeoutExceptionTest.php` following the pattern in `tests/PPAExceptionsTest.php`
3. Write class existence test
4. Write inheritance test: `$ref->isSubclassOf(PPAFailedRequestException::class)`
5. Write transitive inheritance test: `$ref->isSubclassOf(\Exception::class)`
6. Write throwability test with `$this->expectException()`
7. Write message preservation test
8. Write catch-as-parent test using try/catch with `PPAFailedRequestException`
9. Write namespace test asserting `'Detain\\MyAdminPleskAutomation'`
10. Run:

```bash
vendor/bin/phpunit tests/PPATimeoutExceptionTest.php
```

**Result**: 7 tests, 7 assertions, all green.

## Common Issues

**Error: `Class "Detain\MyAdminPleskAutomation\Tests\YourTest" not found`**
1. Verify `composer.json` has `autoload-dev` mapping `Detain\MyAdminPleskAutomation\Tests\` to `tests/`
2. Run `composer dump-autoload`
3. Verify test file namespace matches `Detain\MyAdminPleskAutomation\Tests` exactly
4. Verify class name matches filename (e.g., `PPAConnectorTest` in `tests/PPAConnectorTest.php`)

**Error: `Class "Detain\MyAdminPleskAutomation\SomeClass" not found`**
1. Verify `src/SomeClass.php` exists and declares `namespace Detain\MyAdminPleskAutomation;`
2. Run `composer dump-autoload`

**Error: `ReflectionException: Property PPAConnector::$xmlrpcProxy does not exist`**
1. The property name may have changed — check `src/PPAConnector.php` for current property names
2. Update the `setUp()` and test references to match the actual property name

**Error: `Failed asserting that false is true` in testIsInstantiable**
1. The class may be abstract. Use `$ref->isAbstract()` to check, and adjust your assertion
2. If the class has a private constructor (singleton), test with `$this->assertFalse($ref->isInstantiable())` instead

**Error: `Trying to access array offset on value of type null` in source inspection tests**
1. The file path is wrong. Use `__DIR__ . '/../src/FileName.php'` — verify the file exists first with `$this->assertFileExists($path)` as the first test

**Tests pass individually but fail as a suite**
1. Static state leaking between tests. Add a `setUp()` method that resets any static properties via `ReflectionProperty::setValue(null, null)`
2. Check for `$ref->setAccessible(true)` before accessing protected/private members

**PHPUnit warns about risky tests (no assertions)**
1. `phpunit.xml.dist` has `failOnRisky="true"`. Every test method MUST contain at least one assertion
2. If using `expectException()`, that counts as an assertion — but ensure the exception is actually thrown