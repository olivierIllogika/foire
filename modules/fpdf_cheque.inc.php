<?PHP
/*
Olivier Martin

*/

require('fpdf_protection.inc.php');
require('class.num2str.php');


class PDF_Cheque extends PDF_Protection
{
  
function InfoPane()
{
  
    $mois_fr = array("zero","janvier","février","mars","avril","mai","juin","juillet","août","septembre","octobre","novembre","décembre");

//echo $mois_fr[intval(date("m"))].'<br /><br />';

///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
    $date_impression = 'Le '.date('j').' '.$mois_fr[intval(date("m"))].' '.date('Y');
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////

    $this->SetMargins(30,10);


    $title     = "Foire aux Livres";

    //Arial 14
    $this->SetFont('Arial','',16);

    $this->Ln(1);

    //Titre
    $this->Cell(40,2,$title,0,1,'L');
    
    $this->Ln(3);

    $this->Cell(10,2,$date_impression,0,1,'L');

    //Line break
    $this->Ln(10);

    $this->SetFont('Courier','',16);
    $this->SetFillColor(0,0,0);
    $this->Ln(2);

}
  
function ChequePart($name, $amount)
{
  $numLiteral = new Num2Str($amount);
  $amount_literal = '* * * '.$numLiteral->literal().' * * *';

  $pageWidth = 165;

  for ($i = 0; $i < 3; $i++)
  {  
    $name_amount_y = $this->GetY();
    
    $this->Cell($pageWidth,2,$name,0,1,'L');
    $this->SetY($name_amount_y);
    
    $this->Cell($pageWidth,1,$amount.'.00',0,1,'R');
  
    $this->Ln(5);
    $this->Cell($pageWidth,1,$amount_literal,0,1,'C');
    
    if ($i == 0)
    {
      $this->Ln(10);
      $dim = $this->Code39($this->GetX(), $this->GetY(), $name,false,1,5);
    }

    $this->SetY($this->GetY() + 90);
  }

}
  
//Page header
function Header()
{
  $this->InfoPane();
}

//Page footer
function Footer()
{
  //Position at 1.5 cm from bottom
  $this->SetY(-90);

  //Arial italic 8
  $this->InfoPane();
    
}


}

?>
