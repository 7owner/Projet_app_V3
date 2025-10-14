<?php

namespace App\Controller;

use App\Repository\AgentRepository;
use App\Repository\InterventionRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        MaintenanceRepository $maintenanceRepository,
        InterventionRepository $interventionRepository,
        AgentRepository $agentRepository,
        SiteRepository $siteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $activeMaintenances = $maintenanceRepository->count(['etat' => 'En_cours']);
        $ongoingInterventions = $interventionRepository->count([]); // Assuming all are ongoing for now
        $activeAgents = $agentRepository->count(['actif' => true]);
        $sitesUnderContract = $siteRepository->count([]); // Assuming all are under contract for now
        $urgentMaintenances = $maintenanceRepository->findBy(['etat' => 'Bloque']);

        // Fetch monthly maintenance data for the chart
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('month', 'month');
        $rsm->addScalarResult('count', 'count');
        $sql = "SELECT TO_CHAR(date_debut, 'YYYY-MM') as month, COUNT(id) as count FROM maintenance GROUP BY month ORDER BY month ASC";
        $query = $entityManager->createNativeQuery($sql, $rsm);
        $monthlyMaintenanceData = $query->getResult();

        $chartLabels = [];
        $chartData = [];
        foreach ($monthlyMaintenanceData as $data) {
            $chartLabels[] = (new \DateTime($data['month']))->format('M Y');
            $chartData[] = $data['count'];
        }

        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Nombre de maintenances',
                    'data' => $chartData,
                    'backgroundColor' => 'rgba(108, 99, 255, 0.6)',
                    'borderColor' => 'rgba(108, 99, 255, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];

        return $this->render('dashboard/index.html.twig', [
            'activeMaintenances' => $activeMaintenances,
            'ongoingInterventions' => $ongoingInterventions,
            'activeAgents' => $activeAgents,
            'sitesUnderContract' => $sitesUnderContract,
            'urgentMaintenances' => $urgentMaintenances,
            'chartData' => json_encode($chartData)
        ]);
    }
}
