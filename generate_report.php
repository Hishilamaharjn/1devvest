<?php
require('fpdf/fpdf.php');
include('db_connect.php');

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Project Reports',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(10,10,'#',1);
$pdf->Cell(60,10,'Name',1);
$pdf->Cell(80,10,'Description',1);
$pdf->Cell(40,10,'Created At',1);
$pdf->Ln();

$pdf->SetFont('Arial','',11);
$result = $conn->query("SELECT id, name, description, created_at FROM projects");
while($row = $result->fetch_assoc()){
    $pdf->Cell(10,10,$row['id'],1);
    $pdf->Cell(60,10,substr($row['name'],0,25),1);
    $pdf->Cell(80,10,substr($row['description'],0,40),1);
    $pdf->Cell(40,10,$row['created_at'],1);
    $pdf->Ln();
}

$pdf->Output('D','project_report.pdf');
?>
