<?php

namespace App\Controller;

use App\Repository\SaleFavoriteRepository;
use App\Repository\SaleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VendeurController extends AbstractController
{
    #[Route('/vendeur', name: 'app_vendeur')]
    public function index(SaleRepository $saleRepository): Response
    {
        // Récupérer les ventes du vendeur connecté
        $sales = [];
        $totalSales = 0;
        $activeSales = 0;
        $monthlyRevenue = 0.0;
        $totalViews = 0;
        $recentOrders = [];
        $salesByDay = [];
        
        if ($this->getUser()) {
            $sales = $saleRepository->findPopularByVendeur($this->getUser(), 4);
            $allSales = $saleRepository->findByVendeur($this->getUser());
            $totalSales = count($allSales);
            $activeSales = count(array_filter($allSales, fn($sale) => $sale->isActive()));
            $monthlyRevenue = $saleRepository->calculateMonthlyRevenue($this->getUser());
            $totalViews = $saleRepository->calculateTotalViews($this->getUser());
            
            // Récupérer les commandes récentes
            $recentOrders = $saleRepository->findRecentOrdersByVendeur($this->getUser(), 4);
            
            // Calculer les ventes par jour pour le graphique
            $salesByDay = $saleRepository->calculateSalesByDay($this->getUser(), 7);
        }
        
        return $this->render('vendeur/index.html.twig', [
            'sales' => $sales,
            'totalSales' => $totalSales,
            'activeSales' => $activeSales,
            'monthlyRevenue' => $monthlyRevenue,
            'totalViews' => $totalViews,
            'recentOrders' => $recentOrders,
            'salesByDay' => $salesByDay,
        ]);
    }

    #[Route('/vendeur/mes-favoris', name: 'app_vendeur_favoris')]
    public function mesFavoris(SaleFavoriteRepository $saleFavoriteRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $favoris = $user instanceof \App\Entity\Users
            ? $saleFavoriteRepository->findSalesByUser($user)
            : [];

        return $this->render('vendeur/favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }
}
