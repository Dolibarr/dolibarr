<?php
require_once 'FPDF/fpdf.php';
require_once 'connection.php';
session_start();
$id=$_SESSION['LOAY'];
$companyname ="select * from llx_const where name ='MAIN_INFO_SOCIETE_NOM'";
$companyaddress="select * from llx_const where name ='MAIN_INFO_SOCIETE_ADDRESS'";
$companyzip="select * from llx_const where name ='MAIN_INFO_SOCIETE_ZIP'";
$companytown="select * from llx_const where name ='MAIN_INFO_SOCIETE_TOWN'";
$workername="SELECT concat(firstname,'  ',lastname) as wholename FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
$workeradress="SELECT address FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
$workerZIP="SELECT zip FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
$workerID="SELECT office_phone FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
$paymenttype="SELECT concat(code,'  ',libelle) as wholename FROM `llx_c_paiement` WHERE id IN ( SELECT fk_typepayment FROM llx_payment_salary WHERE rowid =$id)";
$hourewage="SELECT thm FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
$houreperW="SELECT weeklyhours FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
$salaryamount="SELECT amount FROM llx_payment_salary where rowid =$id";
$DATE="SELECT datep FROM llx_payment_salary where rowid =$id";
if(isset($_POST['btn_pdf']))
{
    $pdf=new FPDF('P','mm', 'a4');
    $pdf->SetFont('arial', 'b','12');
    $pdf->AddPage();
    $pdf->SetFillColor (220,230,241);
    $pdf->cell('180', '8','  Voucher salary','0','1','C');

    $pdf->cell('180', '8','Company','1','1','C','1');
    $pdf->cell('80', '8','  NAME','1','0','r', '1');

    $result=$con->query($companyname);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',    $row["value"],'1','1','c');
    $pdf->cell('80', '8','  Adress','1','0','r', '1');

    $result = $con->query($companyaddress);
    $row = $result->fetch_assoc();
    $pdf->cell('100', '8',    $row["value"],'1','1','c');
    $pdf->cell('80', '8','  Zipcode','1','0','r', '1');

    $result = $con->query($companyzip);
    $row = $result->fetch_assoc();
    $pdf->cell('100', '8',    $row["value"],'1','1','c');
    $pdf->cell('180', '5','  ','0','1','C');

    $pdf->cell('180', '8','Worker','1','1','C', '1');
    $pdf->cell('80', '8','  NAME','1','0','r', '1');

    $result=$con->query($workername);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',   $row["wholename"],'1','1','c');

    $pdf->cell('80', '8','  Adress','1','0','r', '1');
    $result=$con->query($workeradress);
    $row =$result->fetch_assoc();

    $pdf->cell('100', '8',   $row["address"],'1','1','c');
    $pdf->cell('80', '8','  Zipcode','1','0','r', '1');
    $result=$con->query($workerZIP);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',   $row["zip"],'1','1','c');

    $pdf->cell('80', '8','  ID','1','0','r', '1');


    $result = $con->query($workerID);
    $row = $result->fetch_assoc();
    $pdf->cell('100', '8',   $row["office_phone"],'1','1','c');
    $pdf->cell('180', '5','  ','0','1','C');

    $pdf->cell('180', '8','Basics','1','1','C', '1');
    $pdf->cell('80', '8','  City','1','0','r', '1');
    $result=$con->query($companytown);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',   $row["value"],'1','1','c');
    $pdf->cell('80', '8','  Billing procedure','1','0','r', '1');
    $result=$con->query($paymenttype);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',   $row["wholename"],'1','1','c');
    $pdf->cell('80', '8','  Hourly wages','1','0','r', '1');
    $result=$con->query($hourewage);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',  (float)$row["thm"],'1','1','c');
    $pdf->cell('80', '8','  number of hours per week','1','0','r', '1');
    $result=$con->query($houreperW);
    $row =$result->fetch_assoc();
    $pdf->cell('100', '8',  (float)$row["weeklyhours"],'1','1','c');
    $pdf->cell('180', '5','  ','0','1','C');

    $pdf->cell('80', '8','  Wage','1','0','r', '1');
    $pdf->cell('33.33', '8','Ratio','1','0','r', '1');
    $pdf->cell('33.33', '8','Per hour','1','0','r', '1');
    $pdf->cell('33.33', '8','Per month','1','1','r', '1');
    $pdf->cell('80', '8','  Salary','1','0','r', '1');
    $pdf->cell('33.33', '8',' ','1','0','r');

    $result=$con->query($hourewage);
    $row =$result->fetch_assoc();
    $pdf->cell('33.33', '8',(float)$row["thm"],'1','0','r');

    $result=$con->query($salaryamount);
    $row =$result->fetch_assoc();
    $pdf->cell('33.33', '8',(float)$row["amount"],'1','1','r');
    $pdf->cell('80', '8','  Holiday supplement','1','0','r', '1');
    $pdf->cell('33.33', '8','%8.33','1','0','r');

    $result = $con->query($hourewage);
    $row = $result->fetch_assoc();

    $hourewageaf = (float)$row["thm"] * .0833;

    $pdf->cell('33.33', '8',$hourewageaf,'1','0','r');
    $result=$con->query($salaryamount);
    $row =$result->fetch_assoc();
    $salaryamountaf= (float)$row["amount"]*.0833;
    $pdf->cell('33.33', '8',$salaryamountaf,'1','1','r');

    $pdf->cell('80', '8','  Total','1','0','r', '1');
    $pdf->cell('33.33', '8',' ','1','0','r');

    $result=$con->query($hourewage);
    $row =$result->fetch_assoc();
    $hourwagetotal= (float)$row["thm"]+ $hourewageaf;

    $pdf->cell('33.33', '8',$hourwagetotal,'1','0','r');
    $result=$con->query($salaryamount);
    $row =$result->fetch_assoc();
    $slaryamounttotal=(float)$row["amount"]+$salaryamountaf;
    $pdf->cell('33.33', '8',$slaryamounttotal,'1','1','r');
    $pdf->cell('180', '5','  ','0','1','C');

    $pdf->cell('80', '8','  Tax','1','0','r', '1');
    $pdf->cell('33.33', '8','Ratio','1','0','r', '1');
    $pdf->cell('33.33', '8','Per hour','1','0','r', '1');
    $pdf->cell('33.33', '8','Per month','1','1','r', '1');

    $pdf->cell('80', '8','  AHV/IV/EO','1','0','r', '1');

    $pdf->cell('33.33', '8','%5.3','1','0','r');
    $result=$con->query($hourewage);
    $row =$result->fetch_assoc();
    $hourewagetaxAHV=((float)$row["thm"]+ $hourewageaf)*.053;

    $pdf->cell('33.33', '8',$hourewagetaxAHV,'1','0','r');
    $result=$con->query($salaryamount);
    $row =$result->fetch_assoc();
    $salaryamounttaxAHV= ((float)$row["amount"]+$salaryamountaf)*.053;
    $pdf->cell('33.33', '8',     $salaryamounttaxAHV,'1','1','r');

    $pdf->cell('80', '8','  ALV','1','0','r', '1');
    $pdf->cell('33.33', '8','%1.1','1','0','r');
    $result=$con->query($hourewage);
    $row =$result->fetch_assoc();
    $hourwagetaxalv = ((float)$row["thm"]+ $hourewageaf)*.011;
    $pdf->cell('33.33', '8',$hourwagetaxalv,'1','0','r');

    $result=$con->query($salaryamount);
    $row =$result->fetch_assoc();
    $salaryamounttaxALV= ((float)$row["amount"]+$salaryamountaf)*.011;
    $pdf->cell('33.33', '8', $salaryamounttaxALV,'1','1','r');

    $pdf->cell('80', '8','  Total Tax','1','0','r', '1');
    $pdf->cell('33.33', '8',' ','1','0','r');
    $hourtax=$hourewagetaxAHV+$hourwagetaxalv;
    $pdf->cell('33.33', '8',$hourtax,'1','0','r');
    $slarytax=$salaryamounttaxAHV+$salaryamounttaxALV;
    $pdf->cell('33.33', '8',$slarytax,'1','1','r');

    $pdf->cell('80', '8','  Net salary','1','0','r', '1');
    $pdf->cell('33.33', '8','  ','1','0','r');

    $pdf->cell('33.33', '8',$hourwagetotal-$hourtax,'1','0','r', '1');
    $pdf->cell('33.33', '8',$slaryamounttotal-$slarytax,'1','1','r', '1');
    $pdf->cell('180', '3','  ','0','1','C');


    $pdf->cell('28', '8','  Salary date:','0','0','r');
    $result=$con->query($DATE);
    $row =$result->fetch_assoc();

    $pdf->cell('100', '8',$row["datep"],'0','0','r');
    $pdf->cell('27', '8','Print date: ','0','0','r');
    $pdf->cell('55', '8',date("Y/m/d"),'0','1','r');
    $pdf->cell('180', '8','  ','0','1','C');

    $pdf->cell('180', '3','*The amounts of money mentioned in this report are in dollars$  ','0','1','C');



    $pdf->Output();





}
?>