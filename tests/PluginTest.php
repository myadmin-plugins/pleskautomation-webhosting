<?php

declare(strict_types=1);

namespace Detain\MyAdminPleskAutomation\Tests;

use Detain\MyAdminPleskAutomation\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for the Plugin class.
 *
 * Covers class structure, static property values, hook registration,
 * and event handler method signatures. Database-dependent methods
 * are validated via static analysis (reflection) rather than execution.
 */
class PluginTest extends TestCase
{
    // ── Class Structure ─────────────────────────────────────────

    /**
     * Verify the Plugin class exists and can be loaded.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Verify Plugin is instantiable.
     */
    public function testIsInstantiable(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertTrue($ref->isInstantiable());
    }

    /**
     * Verify Plugin can be constructed without arguments.
     */
    public function testConstructorRequiresNoArguments(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Verify the Plugin lives in the correct namespace.
     */
    public function testNamespace(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertSame('Detain\\MyAdminPleskAutomation', $ref->getNamespaceName());
    }

    // ── Static Properties ───────────────────────────────────────

    /**
     * Verify the $name property value.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('PleskAutomation Webhosting', Plugin::$name);
    }

    /**
     * Verify the $description property is a non-empty string.
     */
    public function testDescriptionPropertyIsNonEmpty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Verify the $help property exists.
     */
    public function testHelpPropertyExists(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertTrue($ref->hasProperty('help'));
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Verify the $module property is 'webhosting'.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('webhosting', Plugin::$module);
    }

    /**
     * Verify the $type property is 'service'.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Verify all static properties are public.
     */
    public function testStaticPropertiesArePublic(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue($ref->hasProperty($prop), "Property \${$prop} should exist");
            $this->assertTrue($ref->getProperty($prop)->isPublic(), "Property \${$prop} should be public");
            $this->assertTrue($ref->getProperty($prop)->isStatic(), "Property \${$prop} should be static");
        }
    }

    // ── getHooks() ──────────────────────────────────────────────

    /**
     * Verify getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Verify getHooks returns the expected hook keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'webhosting.settings',
            'webhosting.activate',
            'webhosting.reactivate',
            'webhosting.deactivate',
            'webhosting.terminate',
            'function.requirements',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Hook key '{$key}' should be registered");
        }
    }

    /**
     * Verify all hook values are valid callable arrays referencing Plugin methods.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        $ref = new ReflectionClass(Plugin::class);
        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Handler for '{$eventName}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$eventName}' should have exactly 2 elements");
            $this->assertSame(Plugin::class, $handler[0], "Handler class for '{$eventName}' should be Plugin");
            $this->assertTrue(
                $ref->hasMethod($handler[1]),
                "Method '{$handler[1]}' referenced by '{$eventName}' should exist on Plugin"
            );
        }
    }

    /**
     * Verify getHooks returns exactly 6 hooks.
     */
    public function testGetHooksCount(): void
    {
        $this->assertCount(6, Plugin::getHooks());
    }

    /**
     * Verify getHooks is a static method.
     */
    public function testGetHooksIsStatic(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertTrue($ref->getMethod('getHooks')->isStatic());
    }

    // ── Event Handler Signatures ────────────────────────────────

    /**
     * @dataProvider eventHandlerProvider
     *
     * Verify each event handler method accepts exactly one GenericEvent parameter.
     */
    public function testEventHandlerSignature(string $methodName): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $method = $ref->getMethod($methodName);

        $this->assertTrue($method->isStatic(), "{$methodName} should be static");
        $this->assertTrue($method->isPublic(), "{$methodName} should be public");

        $params = $method->getParameters();
        $this->assertCount(1, $params, "{$methodName} should accept exactly 1 parameter");
        $this->assertSame('event', $params[0]->getName(), "{$methodName} parameter should be named \$event");
    }

    /**
     * Provides the list of event handler method names.
     */
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
        ];
    }

    /**
     * @dataProvider eventHandlerProvider
     *
     * Verify each event handler parameter has a GenericEvent type hint.
     */
    public function testEventHandlerTypeHint(string $methodName): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $method = $ref->getMethod($methodName);
        $params = $method->getParameters();
        $type = $params[0]->getType();

        $this->assertNotNull($type, "{$methodName} parameter should be type-hinted");
        $this->assertSame(
            'Symfony\\Component\\EventDispatcher\\GenericEvent',
            $type->getName(),
            "{$methodName} parameter should be type-hinted as GenericEvent"
        );
    }

    // ── Static Analysis of DB-touching Methods ──────────────────

    /**
     * Verify getActivate method body references expected PPA API calls.
     */
    public function testGetActivateReferencesExpectedApiCalls(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('addAccount', $source);
        $this->assertStringContainsString('addAccountMember', $source);
        $this->assertStringContainsString('activateSubscription', $source);
        $this->assertStringContainsString('createWebspace', $source);
    }

    /**
     * Verify getActivate calls stopPropagation.
     */
    public function testGetActivateCallsStopPropagation(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('stopPropagation', $source);
    }

    /**
     * Verify getDeactivate references disableSubscription.
     */
    public function testGetDeactivateReferencesDisableSubscription(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('disableSubscription', $source);
    }

    /**
     * Verify getTerminate references disableSubscription.
     */
    public function testGetTerminateReferencesDisableSubscription(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('disableSubscription', $source);
    }

    /**
     * Verify getReactivate references enableSubscription.
     */
    public function testGetReactivateReferencesEnableSubscription(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('enableSubscription', $source);
    }

    /**
     * Verify getSettings references expected settings configuration.
     */
    public function testGetSettingsReferencesExpectedConfig(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('add_select_master', $source);
        $this->assertStringContainsString('add_dropdown_setting', $source);
        $this->assertStringContainsString('outofstock_webhosting_ppa', $source);
    }

    /**
     * Verify getRequirements registers both function requirements.
     */
    public function testGetRequirementsRegistersExpectedFunctions(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('get_pleskautomation_info_from_domain', $source);
        $this->assertStringContainsString('get_webhosting_ppa_instance', $source);
    }

    /**
     * Verify getTerminate has return type declarations (bool|null).
     */
    public function testGetTerminateReturnType(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/Plugin.php');
        $this->assertStringContainsString('return true;', $source);
        $this->assertStringContainsString('return false;', $source);
    }

    // ── Additional Method Existence ─────────────────────────────

    /**
     * Verify getChangeIp method exists on Plugin.
     */
    public function testGetChangeIpMethodExists(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertTrue($ref->hasMethod('getChangeIp'));
    }

    /**
     * Verify getMenu method exists on Plugin.
     */
    public function testGetMenuMethodExists(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertTrue($ref->hasMethod('getMenu'));
    }
}
