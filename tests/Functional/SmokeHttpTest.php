<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SmokeHttpTest extends WebTestCase
{
    public function testLandingIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        Assert::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        Assert::assertTrue($client->getResponse()->isSuccessful());
    }

    public function testDashboardRedirectsWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/app');

        Assert::assertTrue($client->getResponse()->isRedirection(), 'Anonymous user should be redirected (likely to login).');
    }

    public function testUnitTemplateRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/app/unit-template/1');

        Assert::assertTrue($client->getResponse()->isRedirection(), 'Anonymous access to unit builder should redirect.');
    }
}
