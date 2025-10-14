<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AgentControllerTest extends WebTestCase
{
    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/agent/');

        $this->assertResponseRedirects('/login');
    }

    public function testIndexLoggedIn(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('test@example.com'); // Assuming a test user exists

        if (!$testUser) {
            $this->markTestSkipped('Test user not found. Please create a user with email "test@example.com" for this test.');
        }

        $client->loginUser($testUser);
        $client->request('GET', '/agent/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.container h1', 'Liste des Agents');
    }

    public function testNewAgent(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('test@example.com');

        if (!$testUser) {
            $this->markTestSkipped('Test user not found. Please create a user with email "test@example.com" for this test.');
        }

        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/agent/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.container h3', 'Créer un nouvel agent');

                $email = 'john.doe' . uniqid() . '@example.com';
                $form = $crawler->selectButton('Enregistrer')->form([
                    'agent[matricule]' => 'AGENT' . uniqid(),
                    'agent[nom]' => 'Doe',
                    'agent[prenom]' => 'John',
                    'agent[email]' => $email,
            'agent[tel]' => '0123456789',
            'agent[actif]' => true,
            'agent[admin]' => false,
            'agent[dateEntree]' => '2023-01-01',
            'agent[commentaire]' => 'Test agent',
            'agent[agence]' => 1, // Assuming an Agence with ID 1 exists
            // For agentFonctions, this is more complex and might require a separate test or fixture
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/agent/');
        $client->followRedirect();

        $this->assertSelectorTextContains('td:contains("Doe")', 'Doe');
        $this->assertSelectorTextContains('td:contains("John")', 'John');
    }

    // Add tests for show, edit, delete similarly
}
