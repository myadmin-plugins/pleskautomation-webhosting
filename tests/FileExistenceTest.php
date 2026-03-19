<?php

declare(strict_types=1);

namespace Detain\MyAdminPleskAutomation\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests that all expected source files exist and contain
 * the correct class/function declarations.
 */
class FileExistenceTest extends TestCase
{
    /**
     * @dataProvider sourceFileProvider
     *
     * Verify each expected source file exists on disk.
     */
    public function testSourceFileExists(string $relativePath): void
    {
        $fullPath = dirname(__DIR__) . '/src/' . $relativePath;
        $this->assertFileExists($fullPath);
    }

    /**
     * Provides relative paths to all expected source files.
     */
    public function sourceFileProvider(): array
    {
        return [
            'PPAConnector.php' => ['PPAConnector.php'],
            'Plugin.php' => ['Plugin.php'],
            'PPAFailedRequestException.php' => ['PPAFailedRequestException.php'],
            'PPAMalformedRequestException.php' => ['PPAMalformedRequestException.php'],
            'PPADomainDoesNotExistException.php' => ['PPADomainDoesNotExistException.php'],
            'get_pleskautomation_info_from_domain.php' => ['get_pleskautomation_info_from_domain.php'],
            'get_webhosting_ppa_instance.php' => ['get_webhosting_ppa_instance.php'],
        ];
    }

    /**
     * Verify PPAConnector.php declares the PPAConnector class.
     */
    public function testPPAConnectorFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/PPAConnector.php');
        $this->assertStringContainsString('class PPAConnector', $content);
    }

    /**
     * Verify Plugin.php declares the Plugin class.
     */
    public function testPluginFileContainsClassDeclaration(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/Plugin.php');
        $this->assertStringContainsString('class Plugin', $content);
    }

    /**
     * Verify PPAFailedRequestException.php declares the exception class.
     */
    public function testFailedRequestExceptionFileContainsClass(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/PPAFailedRequestException.php');
        $this->assertStringContainsString('class PPAFailedRequestException extends \\Exception', $content);
    }

    /**
     * Verify PPAMalformedRequestException.php declares the exception class.
     */
    public function testMalformedRequestExceptionFileContainsClass(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/PPAMalformedRequestException.php');
        $this->assertStringContainsString('class PPAMalformedRequestException extends \\Exception', $content);
    }

    /**
     * Verify PPADomainDoesNotExistException.php extends PPAFailedRequestException.
     */
    public function testDomainDoesNotExistFileContainsClass(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/PPADomainDoesNotExistException.php');
        $this->assertStringContainsString('class PPADomainDoesNotExistException extends PPAFailedRequestException', $content);
    }

    /**
     * Verify get_pleskautomation_info_from_domain.php declares its function.
     */
    public function testGetPleskautomationInfoFunctionFileContainsDeclaration(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('function get_pleskautomation_info_from_domain', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance.php declares its function.
     */
    public function testGetWebhostingPpaInstanceFileContainsDeclaration(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString('function get_webhosting_ppa_instance', $content);
    }

    /**
     * Verify all source files use the correct namespace declaration.
     */
    public function testNamespaceInClassFiles(): void
    {
        $classFiles = [
            'PPAConnector.php',
            'Plugin.php',
            'PPAFailedRequestException.php',
            'PPAMalformedRequestException.php',
            'PPADomainDoesNotExistException.php',
        ];
        foreach ($classFiles as $file) {
            $content = file_get_contents(dirname(__DIR__) . '/src/' . $file);
            $this->assertStringContainsString(
                'namespace Detain\\MyAdminPleskAutomation;',
                $content,
                "{$file} should declare the correct namespace"
            );
        }
    }

    /**
     * Verify composer.json exists at the package root.
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/composer.json');
    }

    /**
     * Verify composer.json is valid JSON.
     */
    public function testComposerJsonIsValid(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'composer.json should be valid JSON');
        $this->assertArrayHasKey('name', $decoded);
        $this->assertSame('detain/myadmin-pleskautomation-webhosting', $decoded['name']);
    }
}
