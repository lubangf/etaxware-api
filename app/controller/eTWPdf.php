 <?php
/**
 * @name eTWPdf.php
 * @desc This file is part of the etaxware-api app. Extend the TCPDF class to create custom Header and Footer
 * @date: 29-09-2020
 * @file: Api.php
 * @path: ./api/v1/Api.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) d'alytics - All Rights Reserved
 * @version    1.0.0
 */
 
 class eTWPdf extends TCPDF {    
     //Page header
     public function Header() {
         // Logo
         //$image_file = K_PATH_IMAGES.'logo_example.jpg';
         //$this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
         // Set font
         $this->SetFont('helvetica', 'B', 20);
         // Title
         $this->Cell(0, 15, 'e-INVOICE/TAX INVOICE', 0, false, 'C', 0, '', 0, false, 'M', 'M');
     }
     
     // Page footer
     public function Footer() {
         // Position at 15 mm from bottom
         $this->SetY(-15);
         // Set font
         $this->SetFont('helvetica', 'I', 8);
         // Page number
         $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
     }
 }