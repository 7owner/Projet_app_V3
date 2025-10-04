<?php

namespace App\Controller;

use App\Repository\AgentRepository;
use App\Repository\InterventionRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\SiteRepository;
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
        SiteRepository $siteRepository
    ): Response {
        $activeMaintenances = $maintenanceRepository->count(['etat' => 'En_cours']);
        $ongoingInterventions = $interventionRepository->count([]); // Assuming all are ongoing for now
        $activeAgents = $agentRepository->count(['actif' => true]);
        $sitesUnderContract = $siteRepository->count([]); // Assuming all are under contract for now
        $urgentMaintenances = $maintenanceRepository->findBy(['etat' => 'Bloque']);

        // Fetch monthly maintenance data for the chart
        $monthlyMaintenanceData = $maintenanceRepository->createQueryBuilder('m')
            ->select('SUBSTRING(m.dateDebut, 1, 7) as month', 'COUNT(m.id) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

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
