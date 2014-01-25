<?PHP
/*
Olivier Martin

*/

require('fpdf_protection.inc.php');


class PDF_Facture extends PDF_Protection
{
//Page header
function Header($compact = FALSE)
{
    $mois_fr = array("zero","janvier","février","mars","avril","mai","juin","juillet","août","septembre","octobre","novembre","décembre");

//echo $mois_fr[intval(date("m"))].'<br /><br />';

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
    $date_impression = 'Le '.date('j').' '.$mois_fr[intval(date("m"))].' '.date('Y');
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////

    $this->SetMargins(18,10);

    $logo_marg = 10;
    $logo_width = 40;

    $title     = "Foire aux Livres";
    $subtitle  = "Vente de livres usagés sous consigne";
    $infotext[0] = "Association des Étudiants de Polytechnique\n";
    $infotext[1] = "Campus de l'Université de Montréal\n";
    $infotext[2] = "Case postale 6079, Succ. Centre-Ville\n";
    $infotext[3] = "Montréal (Québec) H3C 3A7\n";
    $infotext[4] = "(514) 340-4747";
    $infotext[5] = "foire@step.polymtl.ca";

    if ($compact)
    {
    }
    else
    {
      //Logo

      $this->Image('img/AEP_coul_50.jpg',12+$logo_marg,$logo_marg,$logo_width);
//      $this->Image('images/AEP_coul_50.jpg',12+$logo_marg,$logo_marg,$logo_width);

      //Arial 14
      $this->SetFont('Arial','',24);
      //Move to the right
      $this->Cell($logo_width+$logo_marg);
      $this->Cell(40,$logo_marg-5,'',0,1,'L');
      //Move to the right
      $this->Cell($logo_width+$logo_marg);
      //Titre
      $this->Cell(40,1,$title,0,1,'L');
      //Move to the right
      $this->Cell($logo_width+$logo_marg+5);
      //Arial 10
      $this->SetFontSize(11);
      //sous-Titre
      $this->Cell(40,13,$subtitle,0,1,'L');
      //Arial 10
      $this->SetFontSize(9);
      //Info
      for ($i=0; $i<6; $i++) {
          $this->Cell($logo_width+$logo_marg);
          $this->Cell(40,4,$infotext[$i],0,1,'L');
      }
      
      //Line break
      $this->Ln(10);
    }
    $this->SetFont('Courier','',13);
    $this->Cell(10,4,$date_impression,0,1,'L');
    $this->SetFillColor(0,0,0);
    $this->Cell(120,1,'',1,1,'L',1);
    $this->Ln(2);

}

//Page footer
function Footer()
{
    //Position at 1.5 cm from bottom
    $this->SetY(-15);
    //Arial italic 8
    $this->SetFont('Arial','I',8);
    //Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}


}

?>
