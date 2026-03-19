<?php

declare(strict_types=1);

namespace Detain\MyAdminPleskAutomation\Tests;

use Detain\MyAdminPleskAutomation\PPADomainDoesNotExistException;
use Detain\MyAdminPleskAutomation\PPAFailedRequestException;
use Detain\MyAdminPleskAutomation\PPAMalformedRequestException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the PPA exception hierarchy.
 *
 * Validates class structure, inheritance chains, and throwability
 * without relying on mocks of vendor classes.
 */
class PPAExceptionsTest extends TestCase
{
    // ── PPAFailedRequestException ───────────────────────────────

    /**
     * Verify PPAFailedRequestException class exists.
     */
    public function testFailedRequestExceptionExists(): void
    {
        $this->assertTrue(class_exists(PPAFailedRequestException::class));
    }

    /**
     * Verify PPAFailedRequestException extends \Exception.
     */
    public function testFailedRequestExceptionExtendsException(): void
    {
        $ref = new ReflectionClass(PPAFailedRequestException::class);
        $this->assertTrue($ref->isSubclassOf(\Exception::class));
    }

    /**
     * Verify PPAFailedRequestException is throwable.
     */
    public function testFailedRequestExceptionIsThrowable(): void
    {
        $this->expectException(PPAFailedRequestException::class);
        throw new PPAFailedRequestException('test');
    }

    /**
     * Verify PPAFailedRequestException preserves its message.
     */
    public function testFailedRequestExceptionMessage(): void
    {
        $e = new PPAFailedRequestException('request failed');
        $this->assertSame('request failed', $e->getMessage());
    }

    /**
     * Verify PPAFailedRequestException preserves a custom code.
     */
    public function testFailedRequestExceptionCode(): void
    {
        $e = new PPAFailedRequestException('fail', 42);
        $this->assertSame(42, $e->getCode());
    }

    /**
     * Verify PPAFailedRequestException supports a previous exception.
     */
    public function testFailedRequestExceptionPrevious(): void
    {
        $prev = new \RuntimeException('root cause');
        $e = new PPAFailedRequestException('wrapped', 0, $prev);
        $this->assertSame($prev, $e->getPrevious());
    }

    /**
     * Verify PPAFailedRequestException is in the correct namespace.
     */
    public function testFailedRequestExceptionNamespace(): void
    {
        $ref = new ReflectionClass(PPAFailedRequestException::class);
        $this->assertSame('Detain\\MyAdminPleskAutomation', $ref->getNamespaceName());
    }

    // ── PPAMalformedRequestException ────────────────────────────

    /**
     * Verify PPAMalformedRequestException class exists.
     */
    public function testMalformedRequestExceptionExists(): void
    {
        $this->assertTrue(class_exists(PPAMalformedRequestException::class));
    }

    /**
     * Verify PPAMalformedRequestException extends \Exception.
     */
    public function testMalformedRequestExceptionExtendsException(): void
    {
        $ref = new ReflectionClass(PPAMalformedRequestException::class);
        $this->assertTrue($ref->isSubclassOf(\Exception::class));
    }

    /**
     * Verify PPAMalformedRequestException is throwable.
     */
    public function testMalformedRequestExceptionIsThrowable(): void
    {
        $this->expectException(PPAMalformedRequestException::class);
        throw new PPAMalformedRequestException('malformed');
    }

    /**
     * Verify PPAMalformedRequestException preserves its message.
     */
    public function testMalformedRequestExceptionMessage(): void
    {
        $e = new PPAMalformedRequestException('bad request');
        $this->assertSame('bad request', $e->getMessage());
    }

    /**
     * Verify PPAMalformedRequestException is in the correct namespace.
     */
    public function testMalformedRequestExceptionNamespace(): void
    {
        $ref = new ReflectionClass(PPAMalformedRequestException::class);
        $this->assertSame('Detain\\MyAdminPleskAutomation', $ref->getNamespaceName());
    }

    /**
     * Verify PPAMalformedRequestException does NOT extend PPAFailedRequestException.
     */
    public function testMalformedIsNotSubclassOfFailed(): void
    {
        $ref = new ReflectionClass(PPAMalformedRequestException::class);
        $this->assertFalse($ref->isSubclassOf(PPAFailedRequestException::class));
    }

    // ── PPADomainDoesNotExistException ──────────────────────────

    /**
     * Verify PPADomainDoesNotExistException class exists.
     */
    public function testDomainDoesNotExistExceptionExists(): void
    {
        $this->assertTrue(class_exists(PPADomainDoesNotExistException::class));
    }

    /**
     * Verify PPADomainDoesNotExistException extends PPAFailedRequestException.
     */
    public function testDomainDoesNotExistExtendsFailed(): void
    {
        $ref = new ReflectionClass(PPADomainDoesNotExistException::class);
        $this->assertTrue($ref->isSubclassOf(PPAFailedRequestException::class));
    }

    /**
     * Verify PPADomainDoesNotExistException also extends \Exception transitively.
     */
    public function testDomainDoesNotExistExtendsException(): void
    {
        $ref = new ReflectionClass(PPADomainDoesNotExistException::class);
        $this->assertTrue($ref->isSubclassOf(\Exception::class));
    }

    /**
     * Verify PPADomainDoesNotExistException is throwable.
     */
    public function testDomainDoesNotExistExceptionIsThrowable(): void
    {
        $this->expectException(PPADomainDoesNotExistException::class);
        throw new PPADomainDoesNotExistException('domain not found');
    }

    /**
     * Verify PPADomainDoesNotExistException can be caught as PPAFailedRequestException.
     */
    public function testDomainDoesNotExistCaughtAsFailed(): void
    {
        try {
            throw new PPADomainDoesNotExistException('no domain');
        } catch (PPAFailedRequestException $e) {
            $this->assertSame('no domain', $e->getMessage());
            return;
        }
        $this->fail('PPADomainDoesNotExistException should be catchable as PPAFailedRequestException');
    }

    /**
     * Verify PPADomainDoesNotExistException is in the correct namespace.
     */
    public function testDomainDoesNotExistExceptionNamespace(): void
    {
        $ref = new ReflectionClass(PPADomainDoesNotExistException::class);
        $this->assertSame('Detain\\MyAdminPleskAutomation', $ref->getNamespaceName());
    }

    // ── Cross-exception type checks ─────────────────────────────

    /**
     * Verify PPAMalformedRequestException cannot be caught as PPAFailedRequestException.
     */
    public function testMalformedCannotBeCaughtAsFailed(): void
    {
        $caught = false;
        try {
            throw new PPAMalformedRequestException('malformed');
        } catch (PPAFailedRequestException $e) {
            $this->fail('PPAMalformedRequestException should NOT be catchable as PPAFailedRequestException');
        } catch (PPAMalformedRequestException $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }
}
