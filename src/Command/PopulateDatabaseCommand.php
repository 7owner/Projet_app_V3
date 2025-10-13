<?php

namespace App\Command;

use App\Entity\Adresse;
use App\Entity\Affaire;
use App\Entity\Agence;
use App\Entity\AgenceMembre;
use App\Entity\Agent;
use App\Entity\AgentEquipe;
use App\Entity\AgentFonction;
use App\Entity\Client;
use App\Entity\DocumentsRepertoire;
use App\Entity\Doe;
use App\Entity\Equipe;
use App\Entity\Fonction;
use App\Entity\Formation;
use App\Entity\Images;
use App\Entity\Intervention;
use App\Entity\Maintenance;
use App\Entity\Passeport;
use App\Entity\RapportMaintenance;
use App\Entity\Rendezvous;
use App\Entity\RendezvousImage;
use App\Entity\RenduIntervention;
use App\Entity\RenduInterventionImage;
use App\Entity\Site;
use App\Entity\SiteAffaire;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:populate-database',
    description: 'Populates the database with fake data.',
)]
class PopulateDatabaseCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to populate your database with fake data for testing purposes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create('fr_FR');

        $io->title('Populating database with fake data...');

        // Clear existing data (optional, for fresh start)
        $this->truncateEntities($io);

        // 1. Adresses
        $io->section('Creating Adresses...');
        $adresses = [];
        for ($i = 0; $i < 20; $i++) {
            $adresse = new Adresse();
            $adresse->setLibelle($faker->streetName);
            $adresse->setLigne1($faker->streetAddress);
            $adresse->setCodePostal($faker->postcode);
            $adresse->setVille($faker->city);
            $adresse->setRegion($faker->region);
            $adresse->setPays($faker->country);
            $adresse->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $adresse->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($adresse);
            $adresses[] = $adresse;
        }
        $io->progressStart(count($adresses));
        foreach ($adresses as $adresse) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($adresses) . ' Adresses created.');

        // 2. Agences
        $io->section('Creating Agences...');
        $agences = [];
        for ($i = 0; $i < 5; $i++) {
            $agence = new Agence();
            $agence->setTitre($faker->company . ' Agence');
            $agence->setDesignation($faker->catchPhrase);
            $agence->setAdresse($faker->randomElement($adresses));
            $agence->setTelephone($faker->phoneNumber);
            $agence->setEmail($faker->companyEmail);
            $agence->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $agence->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($agence);
            $agences[] = $agence;
        }
        $io->progressStart(count($agences));
        foreach ($agences as $agence) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($agences) . ' Agences created.');

        // 3. Users (for authentication)
        $io->section('Creating Users...');
        $users = [];
        for ($i = 0; $i < 15; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setRoles(['ROLE_USER']);
            $this->entityManager->persist($user);
            $users[] = $user;
        }
        // Add an admin user
        $adminUser = new User();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'admin'));
        $adminUser->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($adminUser);
        $users[] = $adminUser;

        $io->progressStart(count($users));
        foreach ($users as $user) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($users) . ' Users created.');

        // 4. Agents
        $io->section('Creating Agents...');
        $agents = [];
        // Ensure we have enough users for agents
        $availableUsers = array_slice($users, 0, 10); // Take first 10 users for agents
        foreach ($availableUsers as $user) {
            $agent = new Agent();
            $agent->setMatricule($faker->unique()->bothify('MAT###??'));
            $agent->setNom($faker->name);
            $agent->setAdmin($faker->boolean(10)); // 10% chance of being admin
            $agent->setEmail($faker->unique()->email);
            $agent->setTel($faker->phoneNumber);
            $agent->setActif($faker->boolean(80)); // 80% chance of being active
            $agent->setDateEntree($faker->dateTimeBetween('-5 years', 'now'));
            $agent->setCommentaire($faker->paragraph);
            $agent->setAgence($faker->randomElement($agences));
            $agent->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $agent->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $agent->setUser($user);
            $this->entityManager->persist($agent);
            $agents[] = $agent;
        }
        $io->progressStart(count($agents));
        foreach ($agents as $agent) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($agents) . ' Agents created.');

        // 5. Passeports
        $io->section('Creating Passeports...');
        $passeports = [];
        foreach ($agents as $agent) {
            $passeport = new Passeport();
            $passeport->setAgent($agent);
            $passeport->setPermis($faker->randomElement(['Permis B', 'Permis C', 'Permis D', null]));
            $passeport->setHabilitations($faker->randomElement(['H0B0', 'BR', 'B2V', null]));
            $passeport->setCertifications($faker->randomElement(['CACES', 'SST', 'ISO 45001', null]));
            $passeport->setCommentaire($faker->sentence);
            $passeport->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $passeport->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($passeport);
            $passeports[] = $passeport;
        }
        $io->progressStart(count($passeports));
        foreach ($passeports as $passeport) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($passeports) . ' Passeports created.');

        // 6. Equipes
        $io->section('Creating Equipes...');
        $equipes = [];
        for ($i = 0; $i < 10; $i++) {
            $equipe = new Equipe();
            $equipe->setAgence($faker->randomElement($agences));
            $equipe->setNom($faker->word . ' Équipe');
            $equipe->setDescription($faker->sentence);
            $equipe->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $equipe->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($equipe);
            $equipes[] = $equipe;
        }
        $io->progressStart(count($equipes));
        foreach ($equipes as $equipe) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($equipes) . ' Equipes created.');

        // 7. AgenceMembre
        $io->section('Creating AgenceMembre...');
        $agenceMembres = [];
        foreach ($agences as $agence) {
            $assignedAgents = []; // Keep track of agents assigned to this agence
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1 to 3 members per agence
                $agent = $faker->randomElement($agents);
                // Ensure agent is not already assigned to this agence
                if (!in_array($agent->getMatricule(), $assignedAgents)) {
                    $agenceMembre = new AgenceMembre();
                    $agenceMembre->setAgence($agence);
                    $agenceMembre->setAgent($agent);
                    $agenceMembre->setRole($faker->randomElement(['Admin', 'Manager', 'Membre']));
                    $agenceMembre->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
                    $agenceMembre->setDateFin($faker->dateTimeBetween('now', '+2 years'));
                    $this->entityManager->persist($agenceMembre);
                    $agenceMembres[] = $agenceMembre;
                    $assignedAgents[] = $agent->getMatricule();
                }
            }
        }
        $io->progressStart(count($agenceMembres));
        foreach ($agenceMembres as $agenceMembre) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($agenceMembres) . ' AgenceMembre created.');

        // 8. AgentEquipe
        $io->section('Creating AgentEquipe...');
        $agentEquipes = [];
        foreach ($equipes as $equipe) {
            $assignedAgents = []; // Keep track of agents assigned to this equipe
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1 to 3 agents per equipe
                $agent = $faker->randomElement($agents);
                // Ensure agent is not already assigned to this equipe
                if (!in_array($agent->getMatricule(), $assignedAgents)) {
                    $agentEquipe = new AgentEquipe();
                    $agentEquipe->setEquipe($equipe);
                    $agentEquipe->setAgent($agent);
                    $agentEquipe->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
                    $agentEquipe->setDateFin($faker->dateTimeBetween('now', '+2 years'));
                    $this->entityManager->persist($agentEquipe);
                    $agentEquipes[] = $agentEquipe;
                    $assignedAgents[] = $agent->getMatricule();
                }
            }
        }
        $io->progressStart(count($agentEquipes));
        foreach ($agentEquipes as $agentEquipe) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($agentEquipes) . ' AgentEquipe created.');

        // 9. Fonctions
        $io->section('Creating Fonctions...');
        $fonctions = [];
        $fonctionNames = ['Technicien', 'Chef d\'équipe', 'Responsable QSE', 'Administratif', 'Commercial'];
        foreach ($fonctionNames as $name) {
            $fonction = new Fonction();
            $fonction->setCode(strtoupper(substr($name, 0, 3)) . $faker->unique()->randomNumber(2));
            $fonction->setLibelle($name);
            $fonction->setDescription($faker->sentence);
            $fonction->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $fonction->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($fonction);
            $fonctions[] = $fonction;
        }
        $io->progressStart(count($fonctions));
        foreach ($fonctions as $fonction) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($fonctions) . ' Fonctions created.');

        // 10. AgentFonction
        $io->section('Creating AgentFonction...');
        $agentFonctions = [];
        foreach ($agents as $agent) {
            $assignedFonctions = []; // Keep track of functions assigned to this agent
            for ($i = 0; $i < $faker->numberBetween(1, 2); $i++) { // 1 to 2 fonctions per agent
                $fonction = $faker->randomElement($fonctions);
                // Ensure function is not already assigned to this agent
                if (!in_array($fonction->getId(), $assignedFonctions)) {
                    $agentFonction = new AgentFonction();
                    $agentFonction->setAgent($agent);
                    $agentFonction->setFonction($fonction);
                    $agentFonction->setPrincipal($faker->boolean(30)); // 30% chance of being principal
                    $agentFonction->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
                    $agentFonction->setDateFin($faker->dateTimeBetween('now', '+2 years'));
                    $this->entityManager->persist($agentFonction);
                    $agentFonctions[] = $agentFonction;
                    $assignedFonctions[] = $fonction->getId();
                }
            }
        }
        $io->progressStart(count($agentFonctions));
        foreach ($agentFonctions as $agentFonction) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($agentFonctions) . ' AgentFonction created.');

        // 11. Clients
        $io->section('Creating Clients...');
        $clients = [];
        for ($i = 0; $i < 10; $i++) {
            $client = new Client();
            $client->setNomClient($faker->company);
            $client->setRepresentantNom($faker->name);
            $client->setRepresentantEmail($faker->unique()->companyEmail);
            $client->setRepresentantTel($faker->phoneNumber);
            $client->setAdresse($faker->randomElement($adresses));
            $client->setCommentaire($faker->paragraph);
            $client->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $client->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($client);
            $clients[] = $client;
        }
        $io->progressStart(count($clients));
        foreach ($clients as $client) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($clients) . ' Clients created.');

        // 12. Affaires
        $io->section('Creating Affaires...');
        $affaires = [];
        foreach ($clients as $client) {
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1 to 3 affaires per client
                $affaire = new Affaire();
                $affaire->setNomAffaire($faker->catchPhrase);
                $affaire->setClient($client);
                $affaire->setDescription($faker->paragraph);
                $affaire->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
                $affaire->setDateFin($faker->dateTimeBetween('now', '+2 years'));
                $this->entityManager->persist($affaire);
                $affaires[] = $affaire;
            }
        }
        $io->progressStart(count($affaires));
        foreach ($affaires as $affaire) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($affaires) . ' Affaires created.');

        // 13. Sites
        $io->section('Creating Sites...');
        $sites = [];
        for ($i = 0; $i < 15; $i++) {
            $site = new Site();
            $site->setNomSite($faker->company . ' Site');
            $site->setAdresse($faker->randomElement($adresses));
            $site->setCommentaire($faker->paragraph);
            $site->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $site->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($site);
            $sites[] = $site;
        }
        $io->progressStart(count($sites));
        foreach ($sites as $site) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($sites) . ' Sites created.');

        // 14. SiteAffaire
        $io->section('Creating SiteAffaire...');
        $siteAffaires = [];
        foreach ($sites as $site) {
            $assignedAffaires = []; // Keep track of affaires assigned to this site
            for ($i = 0; $i < $faker->numberBetween(1, 2); $i++) { // 1 to 2 affaires per site
                $affaire = $faker->randomElement($affaires);
                // Ensure affaire is not already assigned to this site
                if (!in_array($affaire->getId(), $assignedAffaires)) {
                    $siteAffaire = new SiteAffaire();
                    $siteAffaire->setSite($site);
                    $siteAffaire->setAffaire($affaire);
                    $siteAffaire->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
                    $siteAffaire->setDateFin($faker->dateTimeBetween('now', '+2 years'));
                    $this->entityManager->persist($siteAffaire);
                    $siteAffaires[] = $siteAffaire;
                    $assignedAffaires[] = $affaire->getId();
                }
            }
        }
        $io->progressStart(count($siteAffaires));
        foreach ($siteAffaires as $siteAffaire) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($siteAffaires) . ' SiteAffaire created.');

        // 15. DOE
        $io->section('Creating DOEs...');
        $does = [];
        foreach ($siteAffaires as $siteAffaire) {
            $doe = new Doe();
            $doe->setSite($siteAffaire->getSite());
            $doe->setAffaire($siteAffaire->getAffaire());
            $doe->setTitre($faker->sentence(3));
            $doe->setDescription($faker->paragraph);
            $doe->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $doe->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($doe);
            $does[] = $doe;
        }
        $io->progressStart(count($does));
        foreach ($does as $doe) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($does) . ' DOEs created.');

        // 16. Maintenances
        $io->section('Creating Maintenances...');
        $maintenances = [];
        foreach ($does as $doe) {
            $maintenance = new Maintenance();
            $maintenance->setDoe($doe);
            $maintenance->setAffaire($doe->getAffaire());
            $maintenance->setTitre($faker->sentence(4));
            $maintenance->setDescription($faker->paragraph);
            $maintenance->setEtat($faker->randomElement(['Pas_commence', 'En_cours', 'Termine']));
            $maintenance->setResponsable($faker->randomElement($agents));
            $maintenance->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
            $maintenance->setDateFin($faker->dateTimeBetween('now', '+2 years'));
            $this->entityManager->persist($maintenance);
            $maintenances[] = $maintenance;
        }
        $io->progressStart(count($maintenances));
        foreach ($maintenances as $maintenance) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($maintenances) . ' Maintenances created.');

        // 17. Interventions
        $io->section('Creating Interventions...');
        $interventions = [];
        foreach ($maintenances as $maintenance) {
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1 to 3 interventions per maintenance
                $intervention = new Intervention();
                $intervention->setMaintenance($maintenance);
                $intervention->setDescription($faker->paragraph);
                $intervention->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
                $intervention->setDateFin($faker->dateTimeBetween('now', '+1 year'));
                // Link to a previous intervention (optional)
                if (!empty($interventions) && $faker->boolean(30)) {
                    $intervention->setInterventionPrecedente($faker->randomElement($interventions));
                }
                $intervention->setDateDebutTs($faker->dateTimeBetween('-1 year', 'now'));
                $intervention->setDateFinTs($faker->dateTimeBetween('now', '+1 year'));
                $this->entityManager->persist($intervention);
                $interventions[] = $intervention;
            }
        }
        $io->progressStart(count($interventions));
        foreach ($interventions as $intervention) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($interventions) . ' Interventions created.');

        // 18. Rendezvous
        $io->section('Creating Rendezvous...');
        $rendezvousList = [];
        foreach ($interventions as $intervention) {
            for ($i = 0; $i < $faker->numberBetween(1, 2); $i++) { // 1 to 2 rendez-vous per intervention
                $rendezvous = new Rendezvous();
                $rendezvous->setTitre($faker->sentence(3)); // Add this line
                $rendezvous->setSite($faker->randomElement($sites));
                $rendezvous->setIntervention($intervention);
                $rendezvous->setSujet($faker->randomElement(['maintenance', 'intervention']));
                $rendezvous->setDateRdv($faker->dateTimeBetween('-6 months', '+6 months'));
                $rendezvous->setHeureRdv($faker->dateTimeBetween('08:00', '18:00'));
                $rendezvous->setDescription($faker->sentence);
                $rendezvous->setStatut($faker->randomElement(['Planifie', 'Confirme', 'Termine', 'Annule']));
                $rendezvous->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
                $rendezvous->setDateFin($faker->dateTimeBetween('now', '+1 year'));
                $this->entityManager->persist($rendezvous);
                $rendezvousList[] = $rendezvous;
            }
        }
        $io->progressStart(count($rendezvousList));
        foreach ($rendezvousList as $rendezvous) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($rendezvousList) . ' Rendezvous created.');

        // 19. RapportMaintenance
        $io->section('Creating RapportMaintenance...');
        $rapports = [];
        foreach ($maintenances as $maintenance) {
            $rapport = new RapportMaintenance();
            $rapport->setMaintenance($maintenance);
            $rapport->setDateRapport($faker->dateTimeBetween('-6 months', 'now'));
            $rapport->setAgent($faker->randomElement($agents));
            $rapport->setNomClient($maintenance->getAffaire()->getClient()->getNomClient());
            $rapport->setAdresseClient($maintenance->getAffaire()->getClient()->getAdresse());
            $rapport->setCommentaireInterne($faker->paragraph);
            $rapport->setMaterielCommander($faker->sentence);
            $rapport->setEtat($faker->randomElement(['Pas_commence', 'En_cours', 'Termine']));
            $rapport->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $rapport->setDateFin($faker->dateTimeBetween('now', '+1 year'));
            $this->entityManager->persist($rapport);
            $rapports[] = $rapport;
        }
        $io->progressStart(count($rapports));
        foreach ($rapports as $rapport) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($rapports) . ' RapportMaintenance created.');

        // 20. Formations
        $io->section('Creating Formations...');
        $formations = [];
        foreach ($agents as $agent) {
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1 to 3 formations per agent
                $formation = new Formation();
                $formation->setAgent($agent);
                $formation->setType($faker->randomElement(['Habilitation', 'Certification', 'Permis']));
                $formation->setLibelle($faker->sentence(3));
                $formation->setDateObtention($faker->dateTimeBetween('-3 years', 'now'));
                $formation->setDateExpiration($faker->dateTimeBetween('now', '+3 years'));
                $formation->setOrganisme($faker->company);
                $formation->setCommentaire($faker->sentence);
                $formation->setDateDebut($faker->dateTimeBetween('-2 years', 'now'));
                $formation->setDateFin($faker->dateTimeBetween('now', '+2 years'));
                $this->entityManager->persist($formation);
                $formations[] = $formation;
            }
        }
        $io->progressStart(count($formations));
        foreach ($formations as $formation) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($formations) . ' Formations created.');

        // 21. Images
        $io->section('Creating Images...');
        $images = [];
        for ($i = 0; $i < 30; $i++) {
            $image = new Images();
            $image->setNomFichier($faker->word . '.jpg');
            $image->setTypeMime('image/jpeg');
            $image->setTailleOctets($faker->numberBetween(10000, 500000));
            $image->setImageBlob(base64_encode($faker->imageUrl(640, 480))); // Store base64 encoded image
            $image->setCommentaireImage($faker->sentence);
            $image->setAuteur($faker->randomElement($agents));
            $image->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $image->setDateFin($faker->dateTimeBetween('now', '+1 year'));
            $this->entityManager->persist($image);
            $images[] = $image;
        }
        $io->progressStart(count($images));
        foreach ($images as $image) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($images) . ' Images created.');

        // 22. RendezvousImage
        $io->section('Creating RendezvousImage...');
        $rendezvousImages = [];
        foreach ($rendezvousList as $rendezvous) {
            if (!empty($images) && $faker->boolean(50)) { // 50% chance to link an image
                $rendezvousImage = new RendezvousImage();
                $rendezvousImage->setRendezvous($rendezvous);
                $rendezvousImage->setImage($faker->randomElement($images));
                $this->entityManager->persist($rendezvousImage);
                $rendezvousImages[] = $rendezvousImage;
            }
        }
        $io->progressStart(count($rendezvousImages));
        foreach ($rendezvousImages as $rendezvousImage) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($rendezvousImages) . ' RendezvousImage created.');

        // 23. RenduIntervention
        $io->section('Creating RenduIntervention...');
        $renduInterventions = [];
        foreach ($interventions as $intervention) {
            if ($faker->boolean(70)) { // 70% chance to have a rendu
                $renduIntervention = new RenduIntervention();
                $renduIntervention->setIntervention($intervention);
                $renduIntervention->setResume($faker->sentence);
                $renduIntervention->setValeur($faker->paragraph);
                $renduIntervention->setDateDebut($faker->dateTimeBetween('-6 months', 'now'));
                $renduIntervention->setDateFin($faker->dateTimeBetween('now', '+6 months'));
                $this->entityManager->persist($renduIntervention);
                $renduInterventions[] = $renduIntervention;
            }
        }
        $io->progressStart(count($renduInterventions));
        foreach ($renduInterventions as $renduIntervention) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($renduInterventions) . ' RenduIntervention created.');

        // 24. RenduInterventionImage
        $io->section('Creating RenduInterventionImage...');
        $renduInterventionImages = [];
        foreach ($renduInterventions as $renduIntervention) {
            if (!empty($images) && $faker->boolean(50)) { // 50% chance to link an image
                $renduInterventionImage = new RenduInterventionImage();
                $renduInterventionImage->setRenduIntervention($renduIntervention);
                $renduInterventionImage->setImage($faker->randomElement($images));
                $this->entityManager->persist($renduInterventionImage);
                $renduInterventionImages[] = $renduInterventionImage;
            }
        }
        $io->progressStart(count($renduInterventionImages));
        foreach ($renduInterventionImages as $renduInterventionImage) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($renduInterventionImages) . ' RenduInterventionImage created.');

        // 25. DocumentsRepertoire
        $io->section('Creating DocumentsRepertoire...');
        $documentsRepertoireList = [];
        $cibleTypes = [
            'Affaire', 'Agent', 'Agence', 'Adresse', 'Client', 'Site', 'Rendezvous', 'DOE',
            'Maintenance', 'Intervention', 'RapportMaintenance', 'Formation', 'Fonction'
        ];
        $allCibles = [
            'Affaire' => $affaires,
            'Agent' => $agents,
            'Agence' => $agences,
            'Adresse' => $adresses,
            'Client' => $clients,
            'Site' => $sites,
            'Rendezvous' => $rendezvousList,
            'DOE' => $does,
            'Maintenance' => $maintenances,
            'Intervention' => $interventions,
            'RapportMaintenance' => $rapports,
            'Formation' => $formations,
            'Fonction' => $fonctions,
        ];

        for ($i = 0; $i < 50; $i++) {
            // Randomly choose between linking to a Client directly or other types
            if ($faker->boolean(30) && !empty($clients)) { // 30% chance to link directly to a Client
                $cibleType = 'Client';
                $cible = $faker->randomElement($clients);
            } else {
                $cibleType = $faker->randomElement($cibleTypes);
                $cibleCollection = $allCibles[$cibleType];

                if (empty($cibleCollection)) {
                    continue; // Skip if no cibles of this type exist yet
                }
                $cible = $faker->randomElement($cibleCollection);
            }

            $document = new DocumentsRepertoire();
            $document->setCibleType($cibleType);
            if ($cible instanceof Agent) {
                $document->setCibleId($cible->getMatricule());
            } else {
                $document->setCibleId((string) $cible->getId()); // Cast to string as cibleId is now string
            }
            $document->setNature($faker->randomElement(['Document', 'Video', 'Audio', 'Autre']));
            $document->setNomFichier($faker->word . '.pdf');
            $document->setTypeMime('application/pdf');
            $document->setTailleOctets($faker->numberBetween(10000, 1000000));
            $document->setCheminFichier($faker->filePath());
            $document->setChecksumSha256($faker->sha256);
            $document->setAuteur($faker->randomElement($agents));
            $document->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
            $document->setDateFin($faker->dateTimeBetween('now', '+1 year'));
            $this->entityManager->persist($document);
            $documentsRepertoireList[] = $document;
        }
        $io->progressStart(count($documentsRepertoireList));
        foreach ($documentsRepertoireList as $document) { $io->progressAdvance(); }
        $io->progressFinish();
        $this->entityManager->flush();
        $io->success(count($documentsRepertoireList) . ' DocumentsRepertoire created.');

        $io->success('Database population complete!');

        return Command::SUCCESS;
    }

    private function truncateEntities(SymfonyStyle $io): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        if ($platform->supportsForeignKeyConstraints()) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        }

        $entities = [
            Adresse::class, Agence::class, User::class, Agent::class, Passeport::class, Equipe::class,
            AgenceMembre::class, AgentEquipe::class, Fonction::class, AgentFonction::class, Client::class,
            Affaire::class, Site::class, SiteAffaire::class, Doe::class, Maintenance::class,
            Intervention::class, Rendezvous::class, RapportMaintenance::class, Formation::class, Images::class,
            RendezvousImage::class, RenduIntervention::class, RenduInterventionImage::class, DocumentsRepertoire::class,
        ];

        foreach ($entities as $entityClass) {
            $cmd = $this->entityManager->getClassMetadata($entityClass);
            $connection->executeStatement('TRUNCATE TABLE `' . $cmd->getTableName() . '`');
            $io->note(sprintf('Truncated table: %s', $cmd->getTableName()));
        }

        if ($platform->supportsForeignKeyConstraints()) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }
        $io->success('All tables truncated.');
    }
}