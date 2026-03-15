<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Enum\StatusReclamation;
use App\Repository\ReclamationRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


#[Route('/reclamation')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'reclamation_index')]
    public function index(ReclamationRepository $reclamationRepository, UsersRepository $userRepository): Response
    {
        $reclamations = $reclamationRepository->findAll();
        
        // Ajouter l'email de l'utilisateur à chaque réclamation
        foreach ($reclamations as $reclamation) {
            $user = $userRepository->find($reclamation->getUserId());
            $reclamation->userEmail = $user ? $user->getEmail() : null;
        }
        
        // Calculer les statistiques avec les 4 statuts
        $stats = [
            'total' => count($reclamations),
            'en_attente' => 0,
            'en_cours' => 0,
            'resolue' => 0,
            'rejetee' => 0
        ];
        
        $types = [];
        
        foreach ($reclamations as $reclamation) {
            $status = $reclamation->getStatusReclamation()->value;
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
            
            $type = $reclamation->getTypeReclamation()->value;
            if (!in_array($type, $types)) {
                $types[] = $type;
            }
        }
        
        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'stats' => $stats,
            'types' => $types,
        ]);
    }

    #[Route('/new', name: 'reclamation_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $reclamation = new Reclamation();
        $reclamation->setDateTime(new \DateTime());
        
        $user = $this->getUser();
        $reclamation->setUserId($user->getId());
        $reclamation->setStatusReclamation(StatusReclamation::EN_ATTENTE);

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reclamation);
            $em->flush();

            $this->addFlash('success', 'Réclamation créée avec succès !');
            return $this->redirectToRoute('reclamation_index_user');
        }

        return $this->render('reclamation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/respond', name: 'reclamation_respond', methods: ['POST'])]
public function respond(
    Request $request,
    Reclamation $reclamation,
    EntityManagerInterface $em,
    UsersRepository $userRepository,
    MailerInterface $mailer
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    if (!isset($data['response']) || empty(trim($data['response']))) {
        return new JsonResponse([
            'success' => false,
            'message' => 'La réponse ne peut pas être vide'
        ], 400);
    }

    try {
        // 1️⃣ Enregistrer la réponse
        $reclamation->setReponseAdmin($data['response']);
        $reclamation->setStatusReclamation(StatusReclamation::RESOLUE);
        $em->flush();

        // 2️⃣ Récupérer le créateur de la réclamation
        $user = $userRepository->find($reclamation->getUserId());

        // 3️⃣ Envoyer l’email
        if ($user && $user->getEmail()) {
            $email = (new Email())
                ->from('kacemi396@gmail.com')
                ->to($user->getEmail())
                ->subject('Réponse à votre réclamation #' . $reclamation->getId())
                ->html("
                    <h2>Bonjour,</h2>
                    <p>Votre réclamation <strong>#{$reclamation->getId()}</strong> a reçu une réponse.</p>
                    <hr>
                    <p><strong>Objet :</strong> {$reclamation->getObjet()}</p>
                    <p><strong>Réponse de l’administrateur :</strong></p>
                    <p>{$reclamation->getReponseAdmin()}</p>
                    <hr>
                    <p>Merci de votre confiance.</p>
                ");

            $mailer->send($email);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Réponse enregistrée et email envoyé'
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage()
        ], 500);
    }
}


    #[Route('/show/{id}', name: 'reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation, UsersRepository $userRepository): Response
    {
        // Récupérer l'email de l'utilisateur pour la page show
        $user = $userRepository->find($reclamation->getUserId());
        $reclamation->userEmail = $user ? $user->getEmail() : null;
        
        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,'user'=>$user
        ]);
    }

    #[Route('/{id}/edit', name: 'reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Réclamation modifiée avec succès !');
            return $this->redirectToRoute('reclamation_index');
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $em->remove($reclamation);
            $em->flush();

            $this->addFlash('success', 'Réclamation supprimée avec succès !');
        }

        return $this->redirectToRoute('reclamation_index');
    }

    #[Route('/mes-reclamations', name: 'reclamation_index_user', methods: ['GET'])]
public function indexUser(ReclamationRepository $reclamationRepository): Response
{
    // Sécurité : utilisateur connecté
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    $user = $this->getUser();

    // Récupérer UNIQUEMENT les réclamations de l'utilisateur connecté
    $reclamations = $reclamationRepository->findBy(
         ['user_id' => $user->getId()],
        ['dateTime' => 'DESC']
    );

    return $this->render('reclamation/index1.html.twig', [
        'reclamations' => $reclamations,
    ]);
}

}