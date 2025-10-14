<?php

namespace App\DataFixtures;

use App\Entity\Agence;
use App\Entity\Agent;
use App\Entity\AgentFonction;
use App\Entity\Affaire;
use App\Entity\Client;
use App\Entity\DocumentsRepertoire; // Added this line
use App\Entity\Doe;
use App\Entity\Equipe;
use App\Entity\Fonction;
use App\Entity\Formation;
use App\Entity\Images;
use App\Entity\Intervention;
use App\Entity\Maintenance;
use App\Entity\Passeport;
use App\Entity\RapportMaintenance;
use App\Entity\RenduIntervention;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Create 5 Agences
        $agences = [];
        for ($i = 0; $i < 5; $i++) {
            $agence = new Agence();
            $agence->setTitre($faker->company);
            $agence->setDesignation($faker->catchPhrase);
            $agence->setEmail($faker->companyEmail);
            $agence->setTelephone($faker->phoneNumber);
            $agence->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $agence->setDateFin($faker->dateTimeBetween($agence->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($agence);
            $agences[] = $agence;
        }

        // Create 5 Equipes
        $equipes = [];
        for ($i = 0; $i < 5; $i++) {
            $equipe = new Equipe();
            $equipe->setAgence($faker->randomElement($agences));
            $equipe->setNom($faker->unique()->word . ' Team');
            $equipe->setDescription($faker->sentence);
            $equipe->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $equipe->setDateFin($faker->dateTimeBetween($equipe->getDateDebut(), '+1 year'));
            $manager->persist($equipe);
            $equipes[] = $equipe;
        }

        // Create 5 Fonctions
        $fonctions = [];
        $fonctionNames = ['Technicien', 'Chef d\'équipe', 'Administrateur', 'Commercial', 'Support'];
        foreach ($fonctionNames as $name) {
            $fonction = new Fonction();
            $fonction->setCode(strtoupper(substr($name, 0, 3)) . $faker->unique()->randomNumber(2));
            $fonction->setLibelle($name);
            $fonction->setDescription($faker->sentence);
            $fonction->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $fonction->setDateFin($faker->dateTimeBetween($fonction->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($fonction);
            $fonctions[] = $fonction;
        }

        // Create a specific test user
        $testUser = new User();
        $testUser->setEmail('test@example.com');
        $testUser->setPassword($this->passwordHasher->hashPassword($testUser, 'password'));
        $testUser->setRoles(['ROLE_USER']);
        $manager->persist($testUser);

        // Create 10 Users and Agents
        $agents = [];
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);

            $agent = new Agent();
            $agent->setMatricule('AGT' . $faker->unique()->randomNumber(4));
            $agent->setNom($faker->lastName);
            $agent->setPrenom($faker->firstName);
            $agent->setEmail($user->getEmail()); // Link agent email to user email
            $agent->setTel($faker->phoneNumber);
            $agent->setActif(true);
            $agent->setAdmin($faker->boolean(10)); // 10% chance of being admin
            $agent->setDateEntree($faker->dateTimeBetween('-5 years', 'now'));
            $agent->setCommentaire($faker->paragraph);
            $agent->setAgence($faker->randomElement($agences));
            $agent->setUser($user); // Link agent to user
            $agent->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $agent->setDateFin($faker->dateTimeBetween($agent->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($agent);
            $agents[] = $agent;

            // Assign random functions to agent
            $numFonctions = $faker->numberBetween(1, 3);
            $assignedFonctions = $faker->randomElements($fonctions, $numFonctions);
            foreach ($assignedFonctions as $assignedFonction) {
                $agentFonction = new AgentFonction();
                $agentFonction->setAgent($agent);
                $agentFonction->setFonction($assignedFonction);
                $agentFonction->setPrincipal($faker->boolean(30)); // 30% chance of being principal
                $agentFonction->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
                $agentFonction->setDateFin($faker->dateTimeBetween($agentFonction->getDateDebut(), '+1 year')); // Explicitly set dateFin
                $manager->persist($agentFonction);
            }
        }

        // Create 15 Formations
        for ($i = 0; $i < 15; $i++) {
            $formation = new Formation();
            $formation->setAgent($faker->randomElement($agents));
            $formation->setType($faker->randomElement(['Sécurité', 'Technique', 'Management']));
            $formation->setLibelle($faker->sentence(3));
            $formation->setDateObtention($faker->dateTimeBetween('-2 years', 'now'));
            if ($faker->boolean(60)) { // 60% chance of having an expiration date
                $formation->setDateExpiration($faker->dateTimeBetween($formation->getDateObtention(), '+3 years'));
            }
            $formation->setOrganisme($faker->company);
            $formation->setCommentaire($faker->paragraph);
            $formation->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $formation->setDateFin($faker->dateTimeBetween($formation->getDateDebut(), '+1 year'));
            $manager->persist($formation);
        }

        // Create Passeport entries for each Agent
        foreach ($agents as $agent) {
            $passeport = new Passeport();
            $passeport->setAgent($agent);
            $passeport->setPermis($faker->boolean(80) ? $faker->randomElement(['B', 'BE', 'C', 'CE']) : null);
            $passeport->setHabilitations($faker->boolean(50) ? $faker->sentence : null);
            $passeport->setCertifications($faker->boolean(50) ? $faker->sentence : null);
            $passeport->setCommentaire($faker->boolean(30) ? $faker->paragraph : null);
            $passeport->setDateDebut($faker->dateTimeBetween('-5 years', 'now'));
            $passeport->setDateFin($faker->dateTimeBetween($passeport->getDateDebut(), '+5 years'));
            $manager->persist($passeport);
        }

        // Create 20 Images
        for ($i = 0; $i < 20; $i++) {
            $image = new Images();
            $image->setNomFichier($faker->word . '.' . $faker->fileExtension);
            $image->setTypeMime($faker->randomElement(['image/jpeg', 'image/png', 'image/gif']));
            $image->setTailleOctets($faker->numberBetween(1000, 500000));
            $image->setImageBlob('dummy_image_data_' . $faker->uuid); // Placeholder for image data
            $image->setCommentaireImage($faker->boolean(70) ? $faker->sentence : null);
            $image->setAuteur($faker->randomElement($agents));
            $image->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $image->setDateFin($faker->dateTimeBetween($image->getDateDebut(), '+1 year'));
            $manager->persist($image);
        }

        // Create 3 Clients
        $clients = [];
        for ($i = 0; $i < 3; $i++) {
            $client = new Client();
            $client->setNomClient($faker->company);
            $client->setRepresentantNom($faker->name);
            $client->setRepresentantEmail($faker->unique()->email);
            $client->setRepresentantTel($faker->phoneNumber);
            $client->setCommentaire($faker->paragraph);
            $client->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $client->setDateFin($faker->dateTimeBetween($client->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($client);
            $clients[] = $client;
        }

        // Create 5 Affaires
        $affaires = [];
        for ($i = 0; $i < 5; $i++) {
            $affaire = new Affaire();
            $affaire->setNomAffaire($faker->catchPhrase);
            $affaire->setClient($faker->randomElement($clients));
            $affaire->setDescription($faker->sentence);
            $affaire->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $affaire->setDateFin($faker->dateTimeBetween($affaire->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($affaire);
            $affaires[] = $affaire;
        }

        // Create 5 Sites
        $sites = [];
        for ($i = 0; $i < 5; $i++) {
            $site = new Site();
            $site->setNomSite($faker->city . ' Site');
            $site->setCommentaire($faker->paragraph);
            $site->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $site->setDateFin($faker->dateTimeBetween($site->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($site);
            $sites[] = $site;
        }

        // Create 15 Maintenances
        $maintenances = [];
        $etatOptions = ['Pas_commence', 'En_cours', 'Termine', 'Bloque'];
        for ($i = 0; $i < 15; $i++) {
            $maintenance = new Maintenance();
            
            // Create a Doe for each Maintenance
            $doe = new Doe();
            $doe->setSite($faker->randomElement($sites));
            $doe->setAffaire($faker->randomElement($affaires));
            $doe->setTitre($faker->unique()->word . ' ' . $i); // Make titre unique
            $doe->setDateDebut($faker->dateTimeBetween('-1 year', 'now')); // Explicitly set dateDebut
            $doe->setDateFin($faker->dateTimeBetween($doe->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($doe);
            $maintenance->setDoe($doe);

            $maintenance->setAffaire($faker->randomElement($affaires));
            $maintenance->setTitre($faker->sentence(3));
            $maintenance->setDescription($faker->paragraph);
            $etat = $faker->randomElement($etatOptions);
            $maintenance->setEtat($etat);
            if ($etat === 'Bloque') {
                $maintenance->setMotifBlocage($faker->sentence);
            }
            $maintenance->setResponsable($faker->randomElement($agents));
            $maintenance->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $maintenance->setDateFin($faker->dateTimeBetween($maintenance->getDateDebut(), '+1 year')); // Explicitly set dateFin
            $manager->persist($maintenance);
            $maintenances[] = $maintenance;
        }

        // Create RapportMaintenance entries
        for ($i = 0; $i < 10; $i++) {
            $rapport = new RapportMaintenance();
            $rapport->setMaintenance($faker->randomElement($maintenances));
            $rapport->setAgent($faker->randomElement($agents));
            $rapport->setDateRapport($faker->dateTimeBetween('-6 months', 'now'));
            $rapport->setEtat($faker->randomElement(['Ouvert', 'Ferme', 'En_attente']));
            $rapport->setCommentaireInterne($faker->paragraph);
            $rapport->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $rapport->setDateFin($faker->dateTimeBetween($rapport->getDateDebut(), '+1 year'));
            $manager->persist($rapport);
        }

        // Create 20 Interventions
        $interventions = [];
        for ($i = 0; $i < 20; $i++) {
            $intervention = new Intervention();
            $intervention->setMaintenance($faker->randomElement($maintenances));
            $intervention->setDescription($faker->paragraph);
            $intervention->setDateDebut($faker->dateTimeBetween('-6 months', 'now'));
            if ($faker->boolean(70)) { // 70% chance of having an end date
                $intervention->setDateFin($faker->dateTimeBetween($intervention->getDateDebut(), '+1 year'));
            }
            $intervention->setDateDebutTs($faker->dateTimeBetween('-6 months', 'now')); // Explicitly set dateDebutTs
            $intervention->setDateFinTs($faker->dateTimeBetween($intervention->getDateDebutTs(), '+1 year')); // Explicitly set dateFinTs
            // For interventionPrecedente, we need to ensure there are existing interventions
            // This part is tricky as it creates a circular dependency if not handled carefully
            // For simplicity, let's skip linking previous interventions in fixtures for now
            // if ($faker->boolean(20) && !empty($interventions)) {
            //     $intervention->setInterventionPrecedente($faker->randomElement($interventions));
            // }
            $manager->persist($intervention);
            $interventions[] = $intervention;
        }

        // Create RenduIntervention entries
        foreach ($interventions as $intervention) {
            if ($faker->boolean(70)) { // 70% chance of an intervention having a rendu
                $rendu = new RenduIntervention();
                $rendu->setIntervention($intervention);
                $rendu->setResume($faker->sentence);
                $rendu->setValeur($faker->randomFloat(2, 100, 1000));
                $rendu->setDateDebut($faker->dateTimeBetween($intervention->getDateDebut(), 'now'));
                $rendu->setDateFin($faker->dateTimeBetween($rendu->getDateDebut(), '+1 day')); // Set dateFin as well
                $manager->persist($rendu);
            }
        }

        // Create 10 Rendezvous
        for ($i = 0; $i < 10; $i++) {
            $rendezvous = new \App\Entity\Rendezvous();
            $rendezvous->setTitre($faker->sentence(3));
            $rendezvous->setDescription($faker->paragraph);
            $rendezvous->setDateDebut($faker->dateTimeBetween('-1 month', '+1 month'));
            $rendezvous->setDateFin($faker->dateTimeBetween($rendezvous->getDateDebut(), '+1 month'));
            $rendezvous->setStatut($faker->randomElement(['Planifié', 'Terminé', 'Annulé']));
            $rendezvous->setSujet($faker->sentence(2));
            $rendezvous->setDateRdv($faker->dateTimeBetween('-1 month', '+1 month'));
            $rendezvous->setHeureRdv($faker->dateTime());
            $rendezvous->setIntervention($faker->randomElement($interventions));
            $rendezvous->setSite($faker->randomElement($sites));
            $manager->persist($rendezvous);
        }

        // Create DocumentsRepertoire entries for each Doe and Maintenance
        foreach ($maintenances as $maintenance) {
            // Document for the Doe associated with this Maintenance
            $doe = $maintenance->getDoe();
            if ($doe) {
                $document = new DocumentsRepertoire();
                $document->setNomFichier($faker->word . '_DOE_' . $doe->getId() . '.' . $faker->fileExtension);
                $document->setTypeMime($faker->randomElement(['image/jpeg', 'application/pdf']));
                $document->setCheminFichier($faker->uuid . '.' . $faker->fileExtension);
                $document->setNature($faker->randomElement(['Document', 'Image']));
                $document->setAuteur($faker->randomElement($agents));
                $document->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
                $document->setDateFin($faker->dateTimeBetween($document->getDateDebut(), '+1 year'));
                $document->setCibleType('Doe');
                $document->setCibleId((string) $doe->getId());
                $manager->persist($document);
            }

            // Document for the Maintenance itself
            $document = new DocumentsRepertoire();
            $document->setNomFichier($faker->word . '_MAINT_' . $maintenance->getId() . '.' . $faker->fileExtension);
            $document->setTypeMime($faker->randomElement(['image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']));
            $document->setCheminFichier($faker->uuid . '.' . $faker->fileExtension);
            $document->setNature($faker->randomElement(['Document', 'Image', 'Rapport']));
            $document->setAuteur($faker->randomElement($agents));
            $document->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $document->setDateFin($faker->dateTimeBetween($document->getDateDebut(), '+1 year'));
            $document->setCibleType('Maintenance');
            $document->setCibleId((string) $maintenance->getId());
            $manager->persist($document);
        }

        $manager->flush();
    }
}
