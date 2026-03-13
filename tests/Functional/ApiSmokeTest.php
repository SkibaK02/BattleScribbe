<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiSmokeTest extends WebTestCase
{
    public function testFactionsEndpointAuthenticated(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/factions', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        Assert::assertSame(200, $client->getResponse()->getStatusCode(), 'GET /api/factions should return 200 for authenticated user');
        Assert::assertNotEmpty($client->getResponse()->getContent(), 'API response should not be empty');
    }

    public function testDivisionsEndpointAuthenticated(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/divisions', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        Assert::assertSame(200, $client->getResponse()->getStatusCode(), 'GET /api/divisions should return 200 for authenticated user');
        Assert::assertNotEmpty($client->getResponse()->getContent(), 'API response should not be empty');
    }

    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = $client->getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('test+'.uniqid().'@example.com');
        $user->setPassword('dummy');
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        return $client;
    }
}
