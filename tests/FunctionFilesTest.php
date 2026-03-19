<?php

declare(strict_types=1);

namespace Detain\MyAdminPleskAutomation\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Static analysis tests for the standalone function files.
 *
 * These files depend on global MyAdmin functions (get_module_settings,
 * get_service_master, etc.) so we validate their structure and content
 * without executing them.
 */
class FunctionFilesTest extends TestCase
{
    // ── get_webhosting_ppa_instance.php ──────────────────────────

    /**
     * Verify the function file opens with a PHP tag.
     */
    public function testGetPpaInstanceStartsWithPhpTag(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringStartsWith('<?php', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance accepts a $server parameter.
     */
    public function testGetPpaInstanceHasServerParameter(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString('$server', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance defaults $server to false.
     */
    public function testGetPpaInstanceDefaultsToFalse(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString('$server = false', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance calls PPAConnector::getInstance.
     */
    public function testGetPpaInstanceCallsGetInstance(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString('PPAConnector::getInstance', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance handles array server data.
     */
    public function testGetPpaInstanceHandlesArrayServer(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString('is_array($server)', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance uses html_entity_decode for credentials.
     */
    public function testGetPpaInstanceDecodesCredentials(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString('html_entity_decode', $content);
    }

    /**
     * Verify get_webhosting_ppa_instance parses credentials with explode(':').
     */
    public function testGetPpaInstanceSplitsCredentials(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_webhosting_ppa_instance.php');
        $this->assertStringContainsString("explode(':', ", $content);
    }

    // ── get_pleskautomation_info_from_domain.php ────────────────

    /**
     * Verify the function file opens with a PHP tag.
     */
    public function testGetInfoFromDomainStartsWithPhpTag(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringStartsWith('<?php', $content);
    }

    /**
     * Verify function accepts a $hostname parameter.
     */
    public function testGetInfoFromDomainHasHostnameParameter(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('$hostname', $content);
    }

    /**
     * Verify function calls pleskintegration.getWebspaceIDByPrimaryDomain.
     */
    public function testGetInfoFromDomainCallsGetWebspaceId(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('getWebspaceIDByPrimaryDomain', $content);
    }

    /**
     * Verify function calls pleskintegration.getWebspace.
     */
    public function testGetInfoFromDomainCallsGetWebspace(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('getWebspace', $content);
    }

    /**
     * Verify function calls getAccountInfo.
     */
    public function testGetInfoFromDomainCallsGetAccountInfo(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('getAccountInfo', $content);
    }

    /**
     * Verify function calls getAccountMembers.
     */
    public function testGetInfoFromDomainCallsGetAccountMembers(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('getAccountMembers', $content);
    }

    /**
     * Verify function calls getMemberFullInfo.
     */
    public function testGetInfoFromDomainCallsGetMemberFullInfo(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('getMemberFullInfo', $content);
    }

    /**
     * Verify function returns an array on success (code references return [...]).
     */
    public function testGetInfoFromDomainReturnsArrayOnSuccess(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('return [$accountId, $memberId, $subscriptoinId, $webspaceId]', $content);
    }

    /**
     * Verify function returns false on exception.
     */
    public function testGetInfoFromDomainReturnsFalseOnError(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('return false;', $content);
    }

    /**
     * Verify function calls PPAConnector::checkResponse for validation.
     */
    public function testGetInfoFromDomainCallsCheckResponse(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/get_pleskautomation_info_from_domain.php');
        $this->assertStringContainsString('PPAConnector::checkResponse', $content);
    }
}
