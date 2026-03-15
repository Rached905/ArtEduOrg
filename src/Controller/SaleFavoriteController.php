<?php

namespace App\Controller;

use App\Entity\Sale;
use App\Entity\SaleFavorite;
use App\Repository\SaleFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SaleFavoriteController extends AbstractController
{
    #[Route('/sale/{id}/favori/add', name: 'app_sale_favori_add', methods: ['POST'])]
    public function add(Sale $sale, EntityManagerInterface $em, SaleFavoriteRepository $repo, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$csrf->isTokenValid(new CsrfToken('favori_add', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        if ($repo->findOneByUserAndSale($user, $sale)) {
            $this->addFlash('info', 'Cette œuvre est déjà dans vos favoris.');
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        $fav = new SaleFavorite();
        $fav->setUser($user);
        $fav->setSale($sale);
        $em->persist($fav);
        $em->flush();
        $this->addFlash('success', 'Œuvre ajoutée aux favoris.');
        $referer = $request->headers->get('Referer', $this->generateUrl('app_sale_details', ['id' => $sale->getId()]));
        return $this->redirect($referer);
    }

    #[Route('/sale/{id}/favori/remove', name: 'app_sale_favori_remove', methods: ['POST'])]
    public function remove(Sale $sale, EntityManagerInterface $em, SaleFavoriteRepository $repo, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$csrf->isTokenValid(new CsrfToken('favori_remove', $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        $fav = $repo->findOneByUserAndSale($user, $sale);
        if ($fav) {
            $em->remove($fav);
            $em->flush();
            $this->addFlash('success', 'Œuvre retirée des favoris.');
        }
        $referer = $request->headers->get('Referer', $this->generateUrl('app_sale_details', ['id' => $sale->getId()]));
        return $this->redirect($referer);
    }
}
