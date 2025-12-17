<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGenerator
{
    public function __construct(
        private Environment $twig
    ) {
    }

    /**
     * Génère un PDF à partir d'un template Twig
     */
    public function generateFromTemplate(string $template, array $context = []): string
    {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);

        // Rendre le template Twig en HTML
        $html = $this->twig->render($template, $context);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Configurer le format et l'orientation
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le PDF
        $dompdf->render();

        // Retourner le contenu du PDF
        return $dompdf->output();
    }

    /**
     * Génère un PDF de contrat de sponsoring
     */
    public function generateContractPdf(object $contract, array $companyInfo = []): string
    {
        return $this->generateFromTemplate('sponsor_contract/pdf.html.twig', [
            'contract' => $contract,
            'company' => $companyInfo,
        ]);
    }
}


