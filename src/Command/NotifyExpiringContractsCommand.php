<?php

namespace App\Command;

use App\Repository\SponsorContractRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:notify-expiring-contracts',
    description: 'Notifie les contrats de sponsoring qui expirent bientôt',
)]
class NotifyExpiringContractsCommand extends Command
{
    public function __construct(
        private readonly SponsorContractRepository $sponsorContractRepository,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Nombre de jours avant expiration pour notifier',
                30
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_OPTIONAL,
                'Envoyer les notifications par email. Spécifiez l\'adresse email ou utilisez "admin" pour l\'admin par défaut',
                false
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Adresse email expéditrice',
                'noreply@votresite.com'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        
        $io->title('Notification des contrats expirants');
        $io->text(sprintf('Recherche des contrats expirant dans les %d prochains jours...', $days));

        $now = new \DateTimeImmutable();
        $expirationDate = $now->modify("+{$days} days");

        // Trouver les contrats qui expirent dans la période spécifiée
        // Utilisation de la méthode existante du repository
        $expiringContracts = $this->sponsorContractRepository->findExpiringWithinDays($days);

        if (empty($expiringContracts)) {
            $io->success('Aucun contrat n\'expire dans les ' . $days . ' prochains jours.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('Trouvé %d contrat(s) expirant dans les %d prochains jours:', count($expiringContracts), $days));
        $io->newLine();

        $tableRows = [];
        foreach ($expiringContracts as $contract) {
            $sponsor = $contract->getSponsor();
            $expiresAt = $contract->getExpiresAt();
            $daysUntilExpiration = $now->diff($expiresAt)->days;

            $tableRows[] = [
                $contract->getContractNumber(),
                $sponsor ? $sponsor->getName() : 'N/A',
                $sponsor ? $sponsor->getEmail() : 'N/A',
                $expiresAt->format('d/m/Y'),
                $daysUntilExpiration . ' jour(s)',
                $contract->getLevel() ? $contract->getLevel()->value : 'N/A',
            ];
        }

        $io->table(
            ['Numéro', 'Sponsor', 'Email', 'Date d\'expiration', 'Jours restants', 'Niveau'],
            $tableRows
        );

        // Afficher un résumé
        $io->newLine();
        $io->section('Résumé');
        $io->listing([
            sprintf('Total de contrats expirants: %d', count($expiringContracts)),
            sprintf('Période: %s - %s', $now->format('d/m/Y'), $expirationDate->format('d/m/Y')),
        ]);

        // Envoyer l'email si l'option est activée
        $emailOption = $input->getOption('email');
        if ($emailOption !== false) {
            try {
                // Déterminer l'adresse email de destination
                $recipientEmail = $emailOption === 'admin' || $emailOption === null 
                    ? 'admin@example.com' 
                    : $emailOption;
                
                $fromEmail = $input->getOption('from');
                
                // Préparer les données pour le template
                $contractsData = [];
                foreach ($expiringContracts as $contract) {
                    $expiresAt = $contract->getExpiresAt();
                    $daysUntilExpiration = $now->diff($expiresAt)->days;
                    
                    $contractsData[] = [
                        'contractNumber' => $contract->getContractNumber(),
                        'sponsor' => $contract->getSponsor(),
                        'expiresAt' => $expiresAt,
                        'daysUntilExpiration' => $daysUntilExpiration,
                        'level' => $contract->getLevel(),
                    ];
                }
                
                // Générer le contenu HTML de l'email
                $htmlContent = $this->twig->render('emails/expiring_contracts_notification.html.twig', [
                    'contracts' => $contractsData,
                    'days' => $days,
                    'periodStart' => $now,
                    'periodEnd' => $expirationDate,
                ]);
                
                // Générer le contenu texte de l'email
                $textContent = "Notification de Contrats Expirants\n\n";
                $textContent .= sprintf("Nous vous informons que %d contrat(s) expirent dans les %d prochains jours.\n\n", count($expiringContracts), $days);
                $textContent .= "Détails des contrats :\n";
                foreach ($contractsData as $contract) {
                    $textContent .= sprintf(
                        "- %s (%s) - Expire le %s (%d jours restants)\n",
                        $contract['contractNumber'],
                        $contract['sponsor'] ? $contract['sponsor']->getName() : 'N/A',
                        $contract['expiresAt']->format('d/m/Y'),
                        $contract['daysUntilExpiration']
                    );
                }
                
                // Créer et envoyer l'email
                $email = (new Email())
                    ->from($fromEmail)
                    ->to($recipientEmail)
                    ->subject(sprintf('⚠️ %d Contrat(s) Expirant(s) dans les %d Prochains Jours', count($expiringContracts), $days))
                    ->text($textContent)
                    ->html($htmlContent);
                
                $this->mailer->send($email);
                
                $io->success(sprintf('Email envoyé avec succès à %s', $recipientEmail));
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur lors de l\'envoi de l\'email : %s', $e->getMessage()));
                return Command::FAILURE;
            }
        }

        $io->success('Notification terminée avec succès.');

        return Command::SUCCESS;
    }
}

