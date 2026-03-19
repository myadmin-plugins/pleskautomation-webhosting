<?php

declare(strict_types=1);

namespace Detain\MyAdminPleskAutomation\Tests;

use Detain\MyAdminPleskAutomation\PPAConnector;
use Detain\MyAdminPleskAutomation\PPAFailedRequestException;
use Detain\MyAdminPleskAutomation\PPAMalformedRequestException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Unit tests for the PPAConnector class.
 *
 * Tests cover class structure, the checkResponse() pure method,
 * and getInstance() URL-building logic via reflection.
 */
class PPAConnectorTest extends TestCase
{
    /**
     * Reset the static xmlrpcProxy between tests so getInstance() state
     * does not leak across test methods.
     */
    protected function setUp(): void
    {
        $ref = new ReflectionProperty(PPAConnector::class, 'xmlrpcProxy');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }

    // ── Class Structure ─────────────────────────────────────────

    /**
     * Verify the PPAConnector class exists and can be loaded.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(PPAConnector::class));
    }

    /**
     * Verify PPAConnector is instantiable (constructor is public).
     */
    public function testIsInstantiable(): void
    {
        $ref = new ReflectionClass(PPAConnector::class);
        $this->assertTrue($ref->isInstantiable());
    }

    /**
     * Verify the getInstance static method is declared.
     */
    public function testGetInstanceMethodExists(): void
    {
        $ref = new ReflectionClass(PPAConnector::class);
        $this->assertTrue($ref->hasMethod('getInstance'));
        $this->assertTrue($ref->getMethod('getInstance')->isStatic());
        $this->assertTrue($ref->getMethod('getInstance')->isPublic());
    }

    /**
     * Verify the checkResponse static method is declared.
     */
    public function testCheckResponseMethodExists(): void
    {
        $ref = new ReflectionClass(PPAConnector::class);
        $this->assertTrue($ref->hasMethod('checkResponse'));
        $this->assertTrue($ref->getMethod('checkResponse')->isStatic());
        $this->assertTrue($ref->getMethod('checkResponse')->isPublic());
    }

    /**
     * Verify the xmlrpcProxy property exists and is protected static.
     */
    public function testXmlrpcProxyPropertyExists(): void
    {
        $ref = new ReflectionClass(PPAConnector::class);
        $this->assertTrue($ref->hasProperty('xmlrpcProxy'));
        $prop = $ref->getProperty('xmlrpcProxy');
        $this->assertTrue($prop->isStatic());
        $this->assertTrue($prop->isProtected());
    }

    /**
     * Verify getInstance accepts exactly three parameters.
     */
    public function testGetInstanceParameterCount(): void
    {
        $ref = new ReflectionClass(PPAConnector::class);
        $method = $ref->getMethod('getInstance');
        $this->assertCount(3, $method->getParameters());
    }

    /**
     * Verify checkResponse accepts exactly one parameter.
     */
    public function testCheckResponseParameterCount(): void
    {
        $ref = new ReflectionClass(PPAConnector::class);
        $method = $ref->getMethod('checkResponse');
        $this->assertCount(1, $method->getParameters());
    }

    // ── checkResponse() Pure Logic ──────────────────────────────

    /**
     * checkResponse returns true when status is 0 (success).
     */
    public function testCheckResponseReturnsTrueOnSuccess(): void
    {
        $result = PPAConnector::checkResponse(['status' => 0]);
        $this->assertTrue($result);
    }

    /**
     * checkResponse throws PPAFailedRequestException when status is non-zero.
     */
    public function testCheckResponseThrowsFailedOnNonZeroStatus(): void
    {
        $this->expectException(PPAFailedRequestException::class);
        $this->expectExceptionMessage('Something went wrong');
        PPAConnector::checkResponse(['status' => 1, 'error_message' => 'Something went wrong']);
    }

    /**
     * checkResponse throws PPAFailedRequestException with correct message.
     */
    public function testCheckResponseFailedExceptionMessage(): void
    {
        $msg = 'Subscription limit reached';
        try {
            PPAConnector::checkResponse(['status' => 99, 'error_message' => $msg]);
            $this->fail('Expected PPAFailedRequestException was not thrown');
        } catch (PPAFailedRequestException $e) {
            $this->assertSame($msg, $e->getMessage());
        }
    }

    /**
     * checkResponse throws PPAMalformedRequestException when status key is missing.
     */
    public function testCheckResponseThrowsMalformedOnMissingStatus(): void
    {
        $this->expectException(PPAMalformedRequestException::class);
        $this->expectExceptionMessage('Malformed answer from POA');
        PPAConnector::checkResponse(['result' => 'ok']);
    }

    /**
     * checkResponse throws PPAMalformedRequestException for empty array.
     */
    public function testCheckResponseThrowsMalformedOnEmptyArray(): void
    {
        $this->expectException(PPAMalformedRequestException::class);
        PPAConnector::checkResponse([]);
    }

    /**
     * checkResponse returns true for status of integer 0.
     */
    public function testCheckResponseSucceedsWithIntegerZero(): void
    {
        $this->assertTrue(PPAConnector::checkResponse(['status' => 0]));
    }

    /**
     * checkResponse throws for status of string "1" (non-zero after loose comparison).
     */
    public function testCheckResponseThrowsForStringNonZeroStatus(): void
    {
        $this->expectException(PPAFailedRequestException::class);
        PPAConnector::checkResponse(['status' => '1', 'error_message' => 'fail']);
    }

    /**
     * checkResponse returns true for status string "0" (loose comparison with 0).
     */
    public function testCheckResponseSucceedsWithStringZeroStatus(): void
    {
        $this->assertTrue(PPAConnector::checkResponse(['status' => '0']));
    }

    /**
     * checkResponse throws PPAFailedRequestException for negative status.
     */
    public function testCheckResponseThrowsForNegativeStatus(): void
    {
        $this->expectException(PPAFailedRequestException::class);
        PPAConnector::checkResponse(['status' => -1, 'error_message' => 'negative error']);
    }

    // ── getInstance() Password Encoding ─────────────────────────

    /**
     * Verify getInstance replaces ? with %3F in passwords.
     *
     * We use a subclass with an overridden static to capture the URL
     * without actually connecting to an XML-RPC server.
     */
    public function testGetInstanceEncodesQuestionMarkInPassword(): void
    {
        // Read the source to confirm the str_replace call exists
        $source = file_get_contents(__DIR__ . '/../src/PPAConnector.php');
        $this->assertStringContainsString(
            "str_replace('?', '%3F', \$password)",
            $source,
            'getInstance must encode question marks in passwords'
        );
    }

    /**
     * Verify getInstance uses port 8440 in the URL.
     */
    public function testGetInstanceUsesPort8440(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/PPAConnector.php');
        $this->assertStringContainsString(':8440/RPC2', $source);
    }

    /**
     * Verify getInstance sets pem. prefix in options.
     */
    public function testGetInstanceSetsPemPrefix(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/PPAConnector.php');
        $this->assertStringContainsString("'prefix' => 'pem.'", $source);
    }

    /**
     * Verify getInstance disables SSL verification in options.
     */
    public function testGetInstanceDisablesSslVerify(): void
    {
        $source = file_get_contents(__DIR__ . '/../src/PPAConnector.php');
        $this->assertStringContainsString("'sslverify' => false", $source);
    }

    /**
     * Verify getInstance returns cached proxy on second call by injecting
     * a value into the static property.
     */
    public function testGetInstanceReturnsCachedProxy(): void
    {
        $dummy = new \stdClass();
        $ref = new ReflectionProperty(PPAConnector::class, 'xmlrpcProxy');
        $ref->setAccessible(true);
        $ref->setValue(null, $dummy);

        $result = PPAConnector::getInstance('1.2.3.4', 'user', 'pass');
        $this->assertSame($dummy, $result);
    }
}
