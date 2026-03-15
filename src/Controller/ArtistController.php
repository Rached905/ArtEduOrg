<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use App\Repository\SaleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/artist')]
final class ArtistController extends AbstractController
{
    #[Route(name: 'app_artist_index', methods: ['GET'])]
    public function index(UsersRepository $usersRepository): Response
    {
        // Récupérer tous les utilisateurs avec le rôle VENDEUR (Role::USER = 'VENDEUR')
        $artists = $usersRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', \App\Enum\Role::USER)
            ->orderBy('u.fullname', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('artist/index.html.twig', [
            'artists' => $artists,
        ]);
    }

    #[Route('/{id}', name: 'app_artist_show', methods: ['GET'])]
    public function show(int $id, UsersRepository $usersRepository, SaleRepository $saleRepository): Response
    {
        $artist = $usersRepository->find($id);

        if (!$artist) {
            throw $this->createNotFoundException('Artiste non trouvé');
        }

        // Vérifier que l'utilisateur est bien un vendeur/artiste
        $role = $artist->getRole();
        if ($role !== \App\Enum\Role::USER) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un artiste');
        }

        // Récupérer toutes les ventes de cet artiste
        $sales = $saleRepository->findByVendeur($artist);

        return $this->render('artist/show.html.twig', [
            'artist' => $artist,
            'sales' => $sales,
        ]);
    }
}

