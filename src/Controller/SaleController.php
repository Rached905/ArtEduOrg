<?php

namespace App\Controller;

use App\Entity\Sale;
use App\Entity\SaleImage;
use App\Enum\SaleStatus;
use App\Form\SaleType;
use App\Form\SaleOwnerType;
use App\Repository\SaleFavoriteRepository;
use App\Repository\SaleRepository;
use App\Service\SaleImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sale')]
final class SaleController extends AbstractController
{
    private const SOLD_STATUSES = [SaleStatus::PAYE, SaleStatus::VENDUE, SaleStatus::PAYER];

    private static function isSaleSold(Sale $sale): bool
    {
        $status = $sale->getStatus();
        return $status !== null && in_array($status, self::SOLD_STATUSES, true);
    }
    #[Route(name: 'app_sale_index', methods: ['GET'])]
    public function index(SaleRepository $saleRepository): Response
    {
        // Récupérer les ventes selon le rôle
        $sales = [];
        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin voit toutes les ventes
            $sales = $saleRepository->findAllOrderedByStatus();
        } elseif ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            // Vendeur voit uniquement ses ventes
            if ($this->getUser()) {
                $sales = $saleRepository->findByVendeur($this->getUser());
            }
        } else {
            // Client voit toutes les ventes actives
            $sales = $saleRepository->findAllOrderedByStatus();
        }
        
        // Déterminer le template selon le rôle
        $template = 'sale/index.html.twig';
        if ($this->isGranted('ROLE_ADMIN')) {
            $template = 'admin/sale/index.html.twig';
        } elseif ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'vendor/sale/index.html.twig';
        } elseif ($this->isGranted('ROLE_CLIENT')) {
            $template = 'client/sale/index.html.twig';
        }
        
        return $this->render($template, [
            'sales' => $sales,
        ]);
    }

    #[Route('/new', name: 'app_sale_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SaleImageUploader $imageUploader): Response
    {
        $sale = new Sale();
        
        // Associer automatiquement le vendeur connecté si c'est un vendeur
        if ($this->getUser() && ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR'))) {
            $sale->setVendeur($this->getUser());
        }
        
        $form = $this->createForm(SaleType::class, $sale, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload d'image principale
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                try {
                    $imagePath = $imageUploader->upload($imageFile);
                    $sale->setImage($imagePath);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image principale : '.$e->getMessage());
                }
            }

            // Gérer l'upload des images multiples
            $imagesFiles = $form->get('images')->getData();
            if ($imagesFiles) {
                try {
                    $imagePaths = $imageUploader->uploadMultiple($imagesFiles);
                    
                    // Créer les entités SaleImage
                    foreach ($imagePaths as $index => $path) {
                        $saleImage = new SaleImage();
                        $saleImage->setPath($path);
                        $saleImage->setSortOrder($index);
                        $saleImage->setIsPrimary($index === 0); // Première image = principale
                        $saleImage->setSale($sale);
                        $entityManager->persist($saleImage);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload des images : '.$e->getMessage());
                }
            }
            
            $entityManager->persist($sale);
            $entityManager->flush();

            $this->addFlash('success', 'La vente a été créée avec succès !');

            return $this->redirectToRoute('app_sale_index', [], Response::HTTP_SEE_OTHER);
        }

        // Déterminer le template selon le rôle
        $template = 'sale/new.html.twig';
        if ($this->isGranted('ROLE_ADMIN')) {
            $template = 'admin/sale/new.html.twig';
        } elseif ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'vendor/sale/new.html.twig';
        }

        return $this->render($template, [
            'sale' => $sale,
            'form' => $form,
            'button_label' => 'Créer la vente',
        ]);
    }

    #[Route('/{id}/details', name: 'app_sale_details', methods: ['GET'])]
    public function details(Sale $sale, \App\Service\InvoiceService $invoiceService, SaleFavoriteRepository $saleFavoriteRepository): Response
    {
        // Récupérer les images triées
        $images = $sale->getSortedImages();
        
        // Vérifier si l'utilisateur est l'acheteur
        $user = $this->getUser();
        $isBuyer = false;
        $invoicePath = null;
        $canEdit = false;
        
        $isBuyerByRelation = $user instanceof \App\Entity\Users && $sale->getAcheteur() && $sale->getAcheteur()->getId() === $user->getId();
        $isBuyerByEmail = $user instanceof \App\Entity\Users && $sale->getBuyerEmail() && strtolower(trim($sale->getBuyerEmail())) === strtolower(trim($user->getEmail() ?? ''));
        $isBuyer = $isBuyerByRelation || $isBuyerByEmail;
        if ($this->isGranted('ROLE_ADMIN') || $isBuyer) {
            $invoicePath = $invoiceService->findInvoiceForSale($sale);
        }

        $isSold = self::isSaleSold($sale);
        $canEditAsVendeur = false;
        $canEditAsAcheteur = false;
        if ($user instanceof \App\Entity\Users) {
            $isVendeur = $sale->getVendeur() && $sale->getVendeur()->getId() === $user->getId();
            $isAcheteur = $isBuyerByRelation || $isBuyerByEmail;
            $canEditAsVendeur = $isVendeur && !$isSold;
            $canEditAsAcheteur = $isAcheteur && $isSold;
        }
        $canEdit = $canEditAsVendeur || $canEditAsAcheteur;
        if ($this->isGranted('ROLE_ADMIN') && $user instanceof \App\Entity\Users && $sale->getVendeur() && $sale->getVendeur()->getId() === $user->getId()) {
            $canEdit = $canEdit || !$isSold;
        }

        $isFavorite = $user instanceof \App\Entity\Users && $saleFavoriteRepository->isFavorite($user, $sale);

        // Déterminer le template selon le rôle
        $template = 'sale/details.html.twig';
        if ($this->isGranted('ROLE_ADMIN')) {
            $template = 'admin/sale/show.html.twig';
        } elseif ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'vendor/sale/show.html.twig';
        } elseif ($this->isGranted('ROLE_CLIENT')) {
            $template = 'client/sale/details.html.twig';
        }

        return $this->render($template, [
            'sale' => $sale,
            'images' => $images,
            'isBuyer' => $isBuyer,
            'invoicePath' => $invoicePath,
            'canEdit' => $canEdit,
            'canEditAsVendeur' => $canEditAsVendeur,
            'canEditAsAcheteur' => $canEditAsAcheteur,
            'isSold' => $isSold,
            'isFavorite' => $isFavorite,
        ]);
    }

    #[Route('/{id}/buy', name: 'app_sale_buy', methods: ['GET', 'POST'])]
    public function buy(Request $request, Sale $sale): Response
    {
        if ($request->isMethod('POST')) {
            // Traiter le formulaire d'achat
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $message = $request->request->get('message');
            
            $this->addFlash('success', 'Votre demande d\'achat a été envoyée avec succès ! Le vendeur vous contactera bientôt.');
            
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        
        return $this->render('sale/buy.html.twig', [
            'sale' => $sale,
        ]);
    }

    #[Route('/{id}/exchange', name: 'app_sale_exchange', methods: ['GET', 'POST'])]
    public function exchange(Request $request, Sale $sale): Response
    {
        if ($request->isMethod('POST')) {
            // Traiter le formulaire d'échange
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $proposedItems = $request->request->get('proposed_items');
            $message = $request->request->get('message');
            
            $this->addFlash('success', 'Votre proposition d\'échange a été envoyée avec succès ! Le vendeur vous contactera bientôt.');
            
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        
        return $this->render('sale/exchange.html.twig', [
            'sale' => $sale,
        ]);
    }

    #[Route('/{id}', name: 'app_sale_show', methods: ['GET'])]
    public function show(Sale $sale, \App\Service\InvoiceService $invoiceService): Response
    {
        // Récupérer les images triées
        $images = $sale->getSortedImages();
        
        $user = $this->getUser();
        $isBuyerByRelation = $user instanceof \App\Entity\Users && $sale->getAcheteur() && $sale->getAcheteur()->getId() === $user->getId();
        $isBuyerByEmail = $user instanceof \App\Entity\Users && $sale->getBuyerEmail() && strtolower(trim($sale->getBuyerEmail())) === strtolower(trim($user->getEmail() ?? ''));
        $isBuyer = $isBuyerByRelation || $isBuyerByEmail;
        $invoicePath = null;
        if ($this->isGranted('ROLE_ADMIN') || $isBuyer) {
            $invoicePath = $invoiceService->findInvoiceForSale($sale);
        }

        $isSold = self::isSaleSold($sale);
        $canEditAsVendeur = false;
        $canEditAsAcheteur = false;
        if ($user instanceof \App\Entity\Users) {
            $isVendeur = $sale->getVendeur() && $sale->getVendeur()->getId() === $user->getId();
            $isAcheteur = $isBuyerByRelation || $isBuyerByEmail;
            $canEditAsVendeur = $isVendeur && !$isSold;
            $canEditAsAcheteur = $isAcheteur && $isSold;
        }
        $canEdit = $canEditAsVendeur || $canEditAsAcheteur;
        if ($this->isGranted('ROLE_ADMIN') && $user instanceof \App\Entity\Users && $sale->getVendeur() && $sale->getVendeur()->getId() === $user->getId()) {
            $canEdit = $canEdit || !$isSold;
        }

        // Déterminer le template selon le rôle
        $template = 'sale/show.html.twig';
        if ($this->isGranted('ROLE_ADMIN')) {
            $template = 'admin/sale/show.html.twig';
        } elseif ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'vendor/sale/show.html.twig';
        }

        return $this->render($template, [
            'sale' => $sale,
            'images' => $images,
            'isBuyer' => $isBuyer,
            'invoicePath' => $invoicePath,
            'canEdit' => $canEdit,
            'canEditAsVendeur' => $canEditAsVendeur,
            'canEditAsAcheteur' => $canEditAsAcheteur,
            'isSold' => $isSold,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sale_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sale $sale, EntityManagerInterface $entityManager, SaleImageUploader $imageUploader): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier une vente.');
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }

        $isSold = self::isSaleSold($sale);
        $isVendeur = $sale->getVendeur() && $sale->getVendeur()->getId() === $user->getId();
        $isAcheteurByRelation = $sale->getAcheteur() && $sale->getAcheteur()->getId() === $user->getId();
        $isAcheteurByEmail = $sale->getBuyerEmail() && strtolower(trim($sale->getBuyerEmail())) === strtolower(trim($user->getEmail() ?? ''));
        $isAcheteur = $isAcheteurByRelation || $isAcheteurByEmail;
        $canEditAsVendeur = $isVendeur && !$isSold;
        $canEditAsAcheteur = $isAcheteur && $isSold;
        $adminOwnSale = $this->isGranted('ROLE_ADMIN') && $isVendeur && !$isSold;

        if (!$canEditAsVendeur && !$canEditAsAcheteur && !$adminOwnSale) {
            if ($isVendeur && $isSold) {
                $this->addFlash('error', 'Cette œuvre a été vendue. Les modifications sont désormais gérées par l\'acheteur.');
            } else {
                $this->addFlash('error', 'Vous n\'avez pas le droit de modifier cette vente.');
            }
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }

        $editorIsAcheteur = $canEditAsAcheteur;
        if ($editorIsAcheteur) {
            $form = $this->createForm(SaleOwnerType::class, $sale);
        } else {
            $form = $this->createForm(SaleType::class, $sale, ['is_edit' => true]);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$editorIsAcheteur) {
                // Gérer l'upload d'image principale
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    if ($sale->getImage()) {
                        $imageUploader->delete($sale->getImage());
                    }
                    try {
                        $imagePath = $imageUploader->upload($imageFile);
                        $sale->setImage($imagePath);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image principale : '.$e->getMessage());
                    }
                }
                $imagesFiles = $form->get('images')->getData();
                if ($imagesFiles) {
                    try {
                        $imagePaths = $imageUploader->uploadMultiple($imagesFiles);
                        $existingImages = $sale->getSaleImages();
                        $lastSortOrder = 0;
                        foreach ($existingImages as $img) {
                            if ($img->getSortOrder() > $lastSortOrder) {
                                $lastSortOrder = $img->getSortOrder();
                            }
                        }
                        foreach ($imagePaths as $index => $path) {
                            $saleImage = new SaleImage();
                            $saleImage->setPath($path);
                            $saleImage->setSortOrder($lastSortOrder + $index + 1);
                            $saleImage->setIsPrimary(false);
                            $saleImage->setSale($sale);
                            $entityManager->persist($saleImage);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload des images : '.$e->getMessage());
                    }
                }
            }
            if ($editorIsAcheteur && $sale->getAcheteur() === null) {
                $sale->setAcheteur($user);
            }
            $entityManager->flush();
            $this->addFlash('success', $editorIsAcheteur ? 'Vos modifications ont été enregistrées.' : 'La vente a été modifiée avec succès !');
            if ($editorIsAcheteur) {
                return $this->redirectToRoute('app_client_mes_achats', [], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_sale_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($editorIsAcheteur) {
            return $this->render('client/sale/edit.html.twig', [
                'sale' => $sale,
                'form' => $form,
                'button_label' => 'Enregistrer',
                'images' => $sale->getSortedImages(),
            ]);
        }
        $template = 'sale/edit.html.twig';
        if ($this->isGranted('ROLE_ADMIN')) {
            $template = 'admin/sale/edit.html.twig';
        } elseif ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'vendor/sale/edit.html.twig';
        }
        return $this->render($template, [
            'sale' => $sale,
            'form' => $form,
            'button_label' => 'Mettre à jour',
            'images' => $sale->getSortedImages(),
        ]);
    }

    #[Route('/{id}', name: 'app_sale_delete', methods: ['POST'])]
    public function delete(Request $request, Sale $sale, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN') && $user instanceof \App\Entity\Users) {
            if (!$sale->getVendeur() || $sale->getVendeur()->getId() !== $user->getId()) {
                $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres ventes.');
                return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
            }
        }
        if (self::isSaleSold($sale)) {
            $isVendeur = $user instanceof \App\Entity\Users && $sale->getVendeur() && $sale->getVendeur()->getId() === $user->getId();
            if ($isVendeur) {
                $this->addFlash('error', 'Une œuvre vendue ne peut plus être supprimée par le vendeur.');
                return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
            }
        }
        if ($this->isCsrfTokenValid('delete'.$sale->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($sale);
            $entityManager->flush();
            
            $this->addFlash('success', 'La vente a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_sale_index', [], Response::HTTP_SEE_OTHER);
    }
}

