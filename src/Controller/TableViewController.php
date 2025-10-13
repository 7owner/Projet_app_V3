<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\DocumentsRepertoire;
use App\Entity\Doe; // Added this line
use App\Entity\Intervention; // Added this line
use App\Entity\Maintenance;
use App\Entity\RapportMaintenance; // Added this line
use App\Entity\RenduIntervention; // Added this line
use Symfony\Component\HttpFoundation\Request;

final class TableViewController extends AbstractController
{
    #[Route('/table/{tableName}', name: 'app_table_view')]
    public function index(string $tableName, EntityManagerInterface $entityManager, Request $request): Response
    {
        $limit = 10; // Number of items per page
        $page = $request->query->getInt('page', 1); // Get current page from request, default to 1
        // Basic security check to prevent arbitrary table access
        $allowedTables = [
            'Adresse', 'Affaire', 'Agence', 'AgenceMembre', 'Agent', 'AgentEquipe',
            'AgentFonction', 'Client', 'DocumentsRepertoire', 'Doe', 'Equipe',
            'Fonction', 'Formation', 'Images', 'Intervention', 'Maintenance',
            'Passeport', 'RapportMaintenance', 'Rendezvous', 'RendezvousImage',
            'RenduIntervention', 'RenduInterventionImage', 'Site', 'SiteAffaire',
            'User', // User entity is kept
            // Add other entities as they are created
        ];

        if (!in_array($tableName, $allowedTables)) {
            throw $this->createNotFoundException('The table does not exist or is not allowed.');
        }

        $repository = $entityManager->getRepository('App\\Entity\\' . $tableName);

        $queryBuilder = $repository->createQueryBuilder('e');

        $searchQuery = $request->query->get('search', '');

        switch ($tableName) {
            case 'Agent':
                $columns = [
                    'matricule' => 'Matricule',
                    'nom' => 'Nom',
                    'email' => 'Email',
                    'tel' => 'Téléphone',
                    'actif' => 'Actif',
                    'agence.titre' => 'Agence',
                    'user.username' => 'Utilisateur',
                ];
                $queryBuilder->leftJoin('e.agence', 'a')->addSelect('a');
                $queryBuilder->leftJoin('e.user', 'u')->addSelect('u');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.matricule', ':search'),
                            $queryBuilder->expr()->like('e.nom', ':search'),
                            $queryBuilder->expr()->like('e.email', ':search'),
                            $queryBuilder->expr()->like('e.tel', ':search'),
                            $queryBuilder->expr()->like('a.titre', ':search'),
                            $queryBuilder->expr()->like('u.username', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            case 'Client':
                $columns = [
                    'id' => 'ID',
                    'nomClient' => 'Nom Client',
                    'representantNom' => 'Nom Représentant',
                    'representantEmail' => 'Email Représentant',
                    'representantTel' => 'Téléphone Représentant',
                    'adresse.ville' => 'Ville',
                    'adresse.pays' => 'Pays',
                ];
                $queryBuilder->leftJoin('e.adresse', 'ad')->addSelect('ad');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.nomClient', ':search'),
                            $queryBuilder->expr()->like('e.representantNom', ':search'),
                            $queryBuilder->expr()->like('e.representantEmail', ':search'),
                            $queryBuilder->expr()->like('e.representantTel', ':search'),
                            $queryBuilder->expr()->like('ad.ville', ':search'),
                            $queryBuilder->expr()->like('ad.pays', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            case 'Maintenance':
                $columns = [
                    'id' => 'ID',
                    'titre' => 'Titre',
                    'description' => 'Description',
                    'etat' => 'État',
                    'responsable.nom' => 'Responsable',
                    'doe.titre' => 'DOE',
                    'affaire.nomAffaire' => 'Affaire',
                ];
                $queryBuilder->leftJoin('e.responsable', 'r')->addSelect('r');
                $queryBuilder->leftJoin('e.doe', 'd')->addSelect('d');
                $queryBuilder->leftJoin('e.affaire', 'af')->addSelect('af');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.titre', ':search'),
                            $queryBuilder->expr()->like('e.description', ':search'),
                            $queryBuilder->expr()->like('e.etat', ':search'),
                            $queryBuilder->expr()->like('r.nom', ':search'),
                            $queryBuilder->expr()->like('d.titre', ':search'),
                            $queryBuilder->expr()->like('af.nomAffaire', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            case 'Intervention':
                $columns = [
                    'id' => 'ID',
                    'description' => 'Description',
                    'dateDebut' => 'Date Début',
                    'dateFin' => 'Date Fin',
                    'maintenance.titre' => 'Maintenance',
                    'interventionPrecedente.id' => 'Intervention Précédente',
                ];
                $queryBuilder->leftJoin('e.maintenance', 'm')->addSelect('m');
                $queryBuilder->leftJoin('e.interventionPrecedente', 'ip')->addSelect('ip');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.description', ':search'),
                            $queryBuilder->expr()->like('m.titre', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            case 'Site':
                $columns = [
                    'id' => 'ID',
                    'nomSite' => 'Nom Site',
                    'adresse.ville' => 'Ville',
                    'adresse.pays' => 'Pays',
                ];
                $queryBuilder->leftJoin('e.adresse', 'ad')->addSelect('ad');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.nomSite', ':search'),
                            $queryBuilder->expr()->like('ad.ville', ':search'),
                            $queryBuilder->expr()->like('ad.pays', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            case 'DocumentsRepertoire':
                $columns = [
                    'id' => 'ID',
                    'nomFichier' => 'Nom Fichier',
                    'cibleType' => 'Type Cible',
                    'cibleId' => 'ID Cible',
                    'nature' => 'Nature',
                    'auteur.nom' => 'Auteur',
                ];
                $queryBuilder->leftJoin('e.auteur', 'a')->addSelect('a');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.nomFichier', ':search'),
                            $queryBuilder->expr()->like('e.cibleType', ':search'),
                            $queryBuilder->expr()->like('e.nature', ':search'),
                            $queryBuilder->expr()->like('a.nom', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            case 'Facture':
                $columns = [
                    'id' => 'ID',
                    'montant' => 'Montant',
                    'dateFacture' => 'Date Facture',
                    'client.nomClient' => 'Client',
                ];
                // Assuming Facture entity will have a ManyToOne relationship with Client
                // $queryBuilder->leftJoin('e.client', 'c')->addSelect('c');
                if ($searchQuery) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('e.montant', ':search'),
                            $queryBuilder->expr()->like('e.dateFacture', ':search')
                            // $queryBuilder->expr()->like('c.nomClient', ':search')
                        )
                    )->setParameter('search', '%' . $searchQuery . '%');
                }
                break;
            // Add cases for other tables here
            default:
                // Default to fetching all data if no specific columns are defined
                // This might need to be refined for very large tables
                $metadata = $entityManager->getClassMetadata('App\\Entity\\' . $tableName);
                $fieldNames = $metadata->getFieldNames();
                foreach ($fieldNames as $fieldName) {
                    $columns[$fieldName] = ucfirst($fieldName);
                }
                if ($searchQuery) {
                    $orConditions = [];
                    foreach ($fieldNames as $fieldName) {
                        // Only add string fields to search
                        if (in_array($metadata->getTypeOfField($fieldName), ['string', 'text'])) {
                            $orConditions[] = $queryBuilder->expr()->like('e.' . $fieldName, ':search');
                        }
                    }
                    if (!empty($orConditions)) {
                        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orConditions))->setParameter('search', '%' . $searchQuery . '%');
                    }
                }
                break;
        }

        if (empty($columns)) {
            $query = $queryBuilder->getQuery();
        } else {
            $query = $queryBuilder->getQuery();
        }

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit

        $data = $paginator->getIterator()->getArrayCopy();
        $totalItems = count($paginator);
        $pages = ceil($totalItems / $limit);

        return $this->render('table_view/index.html.twig', [
            'tableName' => $tableName,
            'data' => $data,
            'columns' => $columns,
            'currentPage' => $page,
            'pages' => $pages,
            'totalItems' => $totalItems,
            'searchQuery' => $searchQuery,
        ]);
    }

    #[Route('/table/{tableName}/{id}', name: 'app_table_view_detail')]
    public function detail(string $tableName, string $id, EntityManagerInterface $entityManager): Response
    {
        // Basic security check to prevent arbitrary table access
        $allowedTables = [
            'Adresse', 'Affaire', 'Agence', 'AgenceMembre', 'Agent', 'AgentEquipe',
            'AgentFonction', 'Client', 'DocumentsRepertoire', 'Doe', 'Equipe',
            'Fonction', 'Formation', 'Images', 'Intervention', 'Maintenance',
            'Passeport', 'RapportMaintenance', 'Rendezvous', 'RendezvousImage',
            'RenduIntervention', 'RenduInterventionImage', 'Site', 'SiteAffaire',
            'User',
        ];

        if (!in_array($tableName, $allowedTables)) {
            throw $this->createNotFoundException('The table does not exist or is not allowed.');
        }

        $repository = $entityManager->getRepository('App\\Entity\\' . $tableName);
        $entity = $repository->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('The entity with ID ' . $id . ' was not found in table ' . $tableName . '.');
        }

        $associatedDocuments = [];
        $documentsRepository = $entityManager->getRepository(DocumentsRepertoire::class);
        $associatedDoes = [];
        $associatedMaintenances = [];

        if ($tableName === 'Doe') {
            // Fetch documents directly linked to this DOE
            $doeDocuments = $documentsRepository->findBy([
                'cibleType' => 'Doe',
                'cibleId' => (string) $entity->getId(),
            ]);
            $associatedDocuments = array_merge($associatedDocuments, $doeDocuments);

            // Fetch documents linked to Maintenance entities associated with this DOE
            $maintenanceRepository = $entityManager->getRepository(Maintenance::class);
            $maintenances = $maintenanceRepository->findBy(['doe' => $entity]);
            foreach ($maintenances as $maintenance) {
                $maintenanceDocuments = $documentsRepository->findBy([
                    'cibleType' => 'Maintenance',
                    'cibleId' => (string) $maintenance->getId(),
                ]);
                $associatedDocuments = array_merge($associatedDocuments, $maintenanceDocuments);
            }
        } elseif ($tableName === 'Client') {
            // Fetch documents directly linked to the client
            $clientDocuments = $documentsRepository->findBy([
                'cibleType' => 'Client',
                'cibleId' => (string) $entity->getId(),
            ]);
            $associatedDocuments = array_merge($associatedDocuments, $clientDocuments);

            // Fetch documents linked to DOEs associated with this client's affaires
            $affaires = $entity->getAffaires();
            foreach ($affaires as $affaire) {
                $does = $affaire->getDoes();
                foreach ($does as $doe) {
                    $doeDocuments = $documentsRepository->findBy([
                        'cibleType' => 'Doe',
                        'cibleId' => (string) $doe->getId(),
                    ]);
                    $associatedDocuments = array_merge($associatedDocuments, $doeDocuments);
                }
            }
        } elseif ($tableName === 'Maintenance') {
            $associatedDocuments = $documentsRepository->findBy([
                'cibleType' => 'Maintenance',
                'cibleId' => (string) $entity->getId(),
            ]);

            $rapportMaintenanceRepository = $entityManager->getRepository(RapportMaintenance::class);
            $associatedRapports = $rapportMaintenanceRepository->findBy(['maintenance' => $entity]);

            $interventionRepository = $entityManager->getRepository(Intervention::class);
            $renduInterventionRepository = $entityManager->getRepository(RenduIntervention::class);

            $interventionsForMaintenance = $interventionRepository->findBy(['maintenance' => $entity]);
            $associatedRendus = [];
            foreach ($interventionsForMaintenance as $intervention) {
                $rendusForIntervention = $renduInterventionRepository->findBy(['intervention' => $intervention]);
                $associatedRendus = array_merge($associatedRendus, $rendusForIntervention);
            }
        } elseif ($tableName === 'Site') {
            $doeRepository = $entityManager->getRepository(Doe::class);
            $associatedDoes = $doeRepository->findBy(['site' => $entity]);

            $maintenanceRepository = $entityManager->getRepository(Maintenance::class);
            $rapportMaintenanceRepository = $entityManager->getRepository(RapportMaintenance::class);
            $interventionRepository = $entityManager->getRepository(Intervention::class);
            $renduInterventionRepository = $entityManager->getRepository(RenduIntervention::class);

            foreach ($associatedDoes as $doe) {
                $maintenancesForDoe = $maintenanceRepository->findBy(['doe' => $doe]);
                $associatedMaintenances = array_merge($associatedMaintenances, $maintenancesForDoe);

                // Also fetch documents for each Doe associated with this Site
                $doeDocuments = $documentsRepository->findBy([
                    'cibleType' => 'Doe',
                    'cibleId' => (string) $doe->getId(),
                ]);
                $associatedDocuments = array_merge($associatedDocuments, $doeDocuments);

                // And documents for each Maintenance associated with this Doe
                foreach ($maintenancesForDoe as $maintenance) {
                    $maintenanceDocuments = $documentsRepository->findBy([
                        'cibleType' => 'Maintenance',
                        'cibleId' => (string) $maintenance->getId(),
                    ]);
                    $associatedDocuments = array_merge($associatedDocuments, $maintenanceDocuments);

                    // Fetch Rapports for this Maintenance
                    $rapportsForMaintenance = $rapportMaintenanceRepository->findBy(['maintenance' => $maintenance]);
                    // Attach rapports to the maintenance object for easier access in Twig
                    $maintenance->associatedRapports = $rapportsForMaintenance;

                    // Fetch Rendus for this Maintenance (via Interventions)
                    $interventionsForMaintenance = $interventionRepository->findBy(['maintenance' => $maintenance]);
                    $rendusForMaintenance = [];
                    foreach ($interventionsForMaintenance as $intervention) {
                        $rendusForIntervention = $renduInterventionRepository->findBy(['intervention' => $intervention]);
                        $rendusForMaintenance = array_merge($rendusForMaintenance, $rendusForIntervention);
                    }
                    // Attach rendus to the maintenance object for easier access in Twig
                    $maintenance->associatedRendus = $rendusForMaintenance;
                }
            }
        }

        return $this->render('table_view/detail.html.twig', [
            'tableName' => $tableName,
            'entity' => $entity,
            'associatedDocuments' => $associatedDocuments,
            'associatedDoes' => $associatedDoes,
            'associatedMaintenances' => $associatedMaintenances,
            'associatedRapports' => $associatedRapports ?? [],
            'associatedRendus' => $associatedRendus ?? [],
        ]);
    }
}

