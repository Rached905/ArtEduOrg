<?php

namespace App\Service;

use App\Entity\Sale;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceService
{
    private string $invoiceDirectory;

    public function __construct(string $kernelProjectDir)
    {
        $this->invoiceDirectory = $kernelProjectDir . '/public/invoices';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->invoiceDirectory)) {
            mkdir($this->invoiceDirectory, 0755, true);
        }
    }

    /**
     * Génère une facture PDF pour un achat direct
     * 
     * @param Sale $sale La vente
     * @param string $clientName Nom du client
     * @param string $clientEmail Email du client
     * @param float $amount Montant payé
     * @param string $paymentIntentId ID du paiement Stripe
     * @return string Chemin du fichier PDF généré
     */
    public function generateDirectPurchaseInvoice(
        Sale $sale,
        string $clientName,
        string $clientEmail,
        float $amount,
        string $paymentIntentId
    ): string {
        // Générer un numéro de facture unique
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string) $sale->getId(), 6, '0', STR_PAD_LEFT) . '-' . substr($paymentIntentId, -6);
        
        // Date de facturation
        $invoiceDate = new \DateTime();
        
        // HTML de la facture
        $html = $this->generateInvoiceHtml($sale, $clientName, $clientEmail, $amount, $invoiceNumber, $invoiceDate, $paymentIntentId);
        
        // Configuration Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Nom du fichier
        $filename = 'facture-' . $invoiceNumber . '.pdf';
        $filepath = $this->invoiceDirectory . '/' . $filename;
        
        // Sauvegarder le PDF
        file_put_contents($filepath, $dompdf->output());
        
        return 'invoices/' . $filename;
    }

    /**
     * Trouve le chemin de la facture pour une vente donnée
     * 
     * @param Sale $sale La vente
     * @return string|null Chemin de la facture ou null si non trouvée
     */
    public function findInvoiceForSale(Sale $sale): ?string
    {
        // Le format du nom de fichier est : facture-INV-YYYY-XXXXXX-XXXXXX.pdf
        // où XXXXXX est le sale ID avec padding et les 6 derniers caractères du paymentIntentId
        // On cherche tous les fichiers qui commencent par facture-INV- et contiennent le sale ID
        
        $saleIdPadded = str_pad((string) $sale->getId(), 6, '0', STR_PAD_LEFT);
        $pattern = 'facture-INV-' . date('Y') . '-' . $saleIdPadded . '-*.pdf';
        
        $files = glob($this->invoiceDirectory . '/' . $pattern);
        
        if (!empty($files)) {
            // Retourner le chemin relatif du premier fichier trouvé
            $filename = basename($files[0]);
            return 'invoices/' . $filename;
        }
        
        // Si pas trouvé avec l'année actuelle, chercher dans toutes les années
        $allPattern = 'facture-INV-*-' . $saleIdPadded . '-*.pdf';
        $allFiles = glob($this->invoiceDirectory . '/' . $allPattern);
        
        if (!empty($allFiles)) {
            // Prendre le plus récent
            usort($allFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $filename = basename($allFiles[0]);
            return 'invoices/' . $filename;
        }
        
        return null;
    }

    /**
     * Génère le HTML de la facture
     */
    private function generateInvoiceHtml(
        Sale $sale,
        string $clientName,
        string $clientEmail,
        float $amount,
        string $invoiceNumber,
        \DateTime $invoiceDate,
        string $paymentIntentId
    ): string {
        $saleTitle = htmlspecialchars($sale->getTitle());
        $saleDescription = htmlspecialchars($sale->getDescription() ?: 'Aucune description');
        $saleType = htmlspecialchars($sale->getType()->value);
        $contactInfo = htmlspecialchars($sale->getContactInfo() ?: 'contact@artedu.com');
        $formattedAmount = number_format($amount, 2, ',', ' ');
        $formattedDate = $invoiceDate->format('d/m/Y à H:i');
        $formattedDateShort = $invoiceDate->format('d/m/Y');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 40px;
            background: #fff;
        }
        .header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 11px;
        }
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-info-left, .invoice-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .invoice-info-right {
            text-align: right;
        }
        .info-box {
            background: #f7fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .info-box h3 {
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 5px 0;
            color: #4a5568;
        }
        .invoice-details {
            margin: 30px 0;
        }
        .invoice-details h2 {
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .item-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .item-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .item-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-line {
            display: flex;
            justify-content: flex-end;
            margin: 5px 0;
            padding: 5px 0;
        }
        .total-line-label {
            width: 150px;
            text-align: right;
            font-weight: 600;
            color: #4a5568;
        }
        .total-line-value {
            width: 100px;
            text-align: right;
            font-weight: 700;
            color: #2d3748;
        }
        .total-amount {
            font-size: 18px;
            color: #198754;
            border-top: 2px solid #198754;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 10px;
        }
        .payment-info {
            background: #d4edda;
            border: 1px solid #198754;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .payment-info h4 {
            color: #198754;
            margin-bottom: 10px;
        }
        .payment-info p {
            margin: 5px 0;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <p>ArtEdu - Galerie d'Art en Ligne</p>
    </div>

    <div class="invoice-info">
        <div class="invoice-info-left">
            <div class="info-box">
                <h3>Informations de facturation</h3>
                <p><strong>Client :</strong> {$clientName}</p>
                <p><strong>Email :</strong> {$clientEmail}</p>
                <p><strong>Date d'achat :</strong> {$formattedDate}</p>
            </div>
        </div>
        <div class="invoice-info-right">
            <div class="info-box">
                <h3>Détails de la facture</h3>
                <p><strong>Numéro :</strong> {$invoiceNumber}</p>
                <p><strong>Date :</strong> {$formattedDateShort}</p>
                <p><strong>Référence paiement :</strong> {$paymentIntentId}</p>
            </div>
        </div>
    </div>

    <div class="invoice-details">
        <h2>Détails de l'achat</h2>
        <table class="item-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Prix unitaire</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{$saleTitle}</strong><br>
                        <small style="color: #718096;">{$saleDescription}</small><br>
                        <small style="color: #718096;">Type : {$saleType}</small>
                    </td>
                    <td class="text-right">{$formattedAmount} €</td>
                    <td class="text-right"><strong>{$formattedAmount} €</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-line">
                <span class="total-line-label">Sous-total HT :</span>
                <span class="total-line-value">{$formattedAmount} €</span>
            </div>
            <div class="total-line">
                <span class="total-line-label">TVA (0%) :</span>
                <span class="total-line-value">0,00 €</span>
            </div>
            <div class="total-line total-amount">
                <span class="total-line-label">TOTAL TTC :</span>
                <span class="total-line-value">{$formattedAmount} €</span>
            </div>
        </div>
    </div>

    <div class="payment-info">
        <h4>✓ Paiement confirmé</h4>
        <p><strong>Mode de paiement :</strong> Carte bancaire (Stripe)</p>
        <p><strong>Statut :</strong> Payé</p>
        <p><strong>Date de paiement :</strong> {$formattedDate}</p>
    </div>

    <div class="footer">
        <p><strong>ArtEdu - Galerie d'Art en Ligne</strong></p>
        <p>Merci pour votre achat ! Vous pouvez venir récupérer votre œuvre à notre galerie.</p>
        <p>Pour toute question, contactez-nous à : {$contactInfo}</p>
        <p style="margin-top: 10px;">Cette facture a été générée automatiquement le {$formattedDate}</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Supprime une facture
     */
    public function deleteInvoice(string $invoicePath): bool
    {
        $fullPath = $this->invoiceDirectory . '/' . basename($invoicePath);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}

