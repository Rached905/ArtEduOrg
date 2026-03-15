<?php

namespace App\Controller;

use App\Entity\Users;
use App\Enum\Role;
use App\Repository\SaleRepository;
use App\Repository\UsersRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

final class WelcomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_welcome');
    }

    #[Route('/welcome', name: 'app_welcome')]
    public function index(
        SaleRepository $saleRepository,
        UsersRepository $usersRepository,
        EventRepository $eventRepository
    ): Response
    {
        // Récupérer les œuvres disponibles et vendues (limité à 8)
        $allSales = $saleRepository->findAll();
        $availableSales = array_slice($saleRepository->findAllAvailableFirst(), 0, 4);
        
        // Récupérer quelques ventes vendues pour montrer la diversité
        $soldSales = [];
        foreach ($allSales as $sale) {
            if (count($soldSales) >= 4) break;
            try {
                if ($sale->getStatus() && in_array($sale->getStatus()->value, ['vendue', 'paye', 'payer'])) {
                    $soldSales[] = $sale;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        $sales = array_merge($availableSales, $soldSales);
        $sales = array_slice($sales, 0, 8); // Limiter à 8 œuvres
        
        // Récupérer les artistes/vendeurs (priorité aux données dynamiques, limité à 4)
        // USER = 'VENDEUR' dans l'enum Role
        $allUsers = $usersRepository->findAll();
        $artists = [];
        foreach ($allUsers as $user) {
            if (count($artists) >= 4) break;
            try {
                if ($user->getRole() && in_array($user->getRole()->value, ['VENDEUR'])) {
                    $artists[] = $user;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Ajouter des artistes virtuels pour compléter jusqu'à 4 (si nécessaire)
        $virtualArtists = [
            (object)[
                'fullname' => 'Leonardo da Vinci',
                'email' => 'leonardo@artmarket.com',
                'isVirtual' => true,
            ],
            (object)[
                'fullname' => 'Pablo Picasso',
                'email' => 'picasso@artmarket.com',
                'isVirtual' => true,
            ],
            (object)[
                'fullname' => 'Vincent van Gogh',
                'email' => 'vangogh@artmarket.com',
                'isVirtual' => true,
            ],
            (object)[
                'fullname' => 'Claude Monet',
                'email' => 'monet@artmarket.com',
                'isVirtual' => true,
            ],
        ];
        
        // Combiner : données dynamiques en premier, puis données virtuelles
        $artists = array_merge($artists, array_slice($virtualArtists, 0, 4 - count($artists)));
        $artists = array_slice($artists, 0, 4);
        
        // Récupérer les événements disponibles/à venir (priorité aux données dynamiques, limité à 4)
        $allEvents = $eventRepository->findAll();
        $events = [];
        $now = new \DateTime();
        foreach ($allEvents as $event) {
            if (count($events) >= 4) break;
            try {
                // Inclure les événements à venir ou en cours
                if ($event->getStartDate() && $event->getStartDate() >= $now) {
                    $events[] = $event;
                } elseif ($event->getStartDate() && $event->getEndDate() && 
                         $event->getStartDate() <= $now && $event->getEndDate() >= $now) {
                    $events[] = $event;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        // Trier par date de début (plus proche en premier)
        usort($events, function($a, $b) {
            $aDate = $a->getStartDate() ?? new \DateTime('9999-12-31');
            $bDate = $b->getStartDate() ?? new \DateTime('9999-12-31');
            return $aDate <=> $bDate;
        });
        
        // Ajouter des événements virtuels pour compléter jusqu'à 4 (si nécessaire)
        $virtualEvents = [
            (object)[
                'title' => 'Exposition Renaissance Italienne',
                'description' => 'Découvrez les plus grandes œuvres de la Renaissance italienne avec des pièces rares de Léonard de Vinci, Michel-Ange et Raphaël.',
                'startDate' => (new \DateTime())->modify('+15 days'),
                'endDate' => (new \DateTime())->modify('+45 days'),
                'location' => 'Musée du Louvre, Paris',
                'price' => '25.00',
                'type' => 'Exposition',
                'isVirtual' => true,
            ],
            (object)[
                'title' => 'Festival d\'Art Contemporain',
                'description' => 'Un événement majeur réunissant les artistes contemporains les plus influents du moment.',
                'startDate' => (new \DateTime())->modify('+30 days'),
                'endDate' => (new \DateTime())->modify('+37 days'),
                'location' => 'Centre Pompidou, Paris',
                'price' => '30.00',
                'type' => 'Festival',
                'isVirtual' => true,
            ],
            (object)[
                'title' => 'Vente aux Enchères d\'Art Moderne',
                'description' => 'Collection exceptionnelle d\'œuvres d\'art moderne incluant des pièces de Picasso, Matisse et Kandinsky.',
                'startDate' => (new \DateTime())->modify('+20 days'),
                'endDate' => (new \DateTime())->modify('+20 days'),
                'location' => 'Hôtel Drouot, Paris',
                'price' => '0.00',
                'type' => 'Vente aux enchères',
                'isVirtual' => true,
            ],
            (object)[
                'title' => 'Atelier de Peinture en Plein Air',
                'description' => 'Rejoignez-nous pour une journée d\'atelier de peinture en plein air dans les jardins de Giverny.',
                'startDate' => (new \DateTime())->modify('+10 days'),
                'endDate' => (new \DateTime())->modify('+10 days'),
                'location' => 'Giverny, Normandie',
                'price' => '50.00',
                'type' => 'Atelier',
                'isVirtual' => true,
            ],
        ];
        
        // Combiner : données dynamiques en premier, puis données virtuelles
        $events = array_merge($events, array_slice($virtualEvents, 0, 4 - count($events)));
        $events = array_slice($events, 0, 4);
        
        return $this->render('welcome/index.html.twig', [
            'controller_name' => 'WelcomeController',
            'hide_navbar' => true,
            'sales' => $sales,
            'artists' => $artists,
            'events' => $events,
        ]);
    }

    #[Route('/welcome/new', name: 'app_welcome_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Si c'est une requête GET, afficher le formulaire
        if ($request->isMethod('GET')) {
            return $this->render('welcome/new.html.twig');
        }

        // Si c'est une requête POST, traiter le formulaire
        try {
            // 1. Vérifier le token CSRF
            $token = $request->request->get('_csrf_token');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('signup', $token))) {
                $this->addFlash('danger', '❌ Token CSRF invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_welcome');
            }

            // 2. Récupérer les données
            $fullname = trim($request->request->get('fullname', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $roleValue = $request->request->get('role', '');
            $acceptTerms = $request->request->get('acceptTerms');

            // 3. Validations
            $errors = [];

            if (empty($fullname) || strlen($fullname) < 2) {
                $errors[] = "Le nom complet doit contenir au moins 2 caractères.";
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'adresse email n'est pas valide.";
            }

            if (empty($password) || strlen($password) < 8) {
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.";
            }

            if (empty($roleValue) || !in_array($roleValue, ['USER', 'AGENT'])) {
                $errors[] = "Veuillez choisir un profil valide (Vendeur ou Client).";
            }

            if (!$acceptTerms) {
                $errors[] = "Vous devez accepter les conditions d'utilisation.";
            }

            // Vérifier si l'email existe déjà
            $existingUser = $em->getRepository(Users::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }

            // Si des erreurs, rediriger
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
                return $this->redirectToRoute('app_welcome');
            }

            // 4. Créer le nouvel utilisateur
            $user = new Users();
            $user->setFullname($fullname);
            $user->setEmail($email);
            
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            // Définir le rôle selon le choix
            if ($roleValue === 'USER') {
                $user->setRole(Role::USER);
            } elseif ($roleValue === 'AGENT') {
                $user->setRole(Role::AGENT);
            }
            
            // isActive est déjà true par défaut dans le constructeur
            $user->setIsActive(true);

            // 5. Sauvegarder en base de données
            $em->persist($user);
            $em->flush();

            // 6. Message de succès et redirection
            $this->addFlash('success', '🎉 Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            
            return $this->redirectToRoute('app_login');

        } catch (\Exception $e) {
            $this->addFlash('danger', '❌ Erreur lors de la création du compte : ' . $e->getMessage());
            return $this->redirectToRoute('app_welcome');
        }
    }
}