<?php

session_start();
$_SESSION['LOAY']="";

require_once 'connection.php';

$companyname ="select * from llx_const where name ='MAIN_INFO_SOCIETE_NOM'";
$companyaddress="select * from llx_const where name ='MAIN_INFO_SOCIETE_ADDRESS'";
$companyzip="select * from llx_const where name ='MAIN_INFO_SOCIETE_ZIP'";
$companytown="select * from llx_const where name ='MAIN_INFO_SOCIETE_TOWN'";


?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="CSS/bootstrap.css">
    <title>Creat pdf file to salary</title>

</head>
<body style="background: #ffffff" >
<?php
$salaryid="";
$nameErr="";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["salaryid"])) {
        $nameErr = "salaryid is required";
    } else {
        $salaryid = test_input($_POST["salaryid"]);
        // check if name only contains letters and whitespace

       }}
function test_input($data){
    $data=trim($data);
    $data=stripcslashes($data);
    return $data;

}
?>

<div class="row" align="center">
    <div class="row">
        <div class="col">
            <div class="card mt-2">
                <div class="container">

                <div class="card-header">

    <form action="pdf_gen.php" method="post" >

    <button  TYPE="submit" name="btn_pdf" class=""btn btn-success  >PDF</button>
&nbsp; &nbsp;
    </form>


                    <form  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" >

                        <input type="number" name="salaryid" value="<?php echo $salaryid; ?>">
                        <input  type="submit"  name="submit" value="Submit">
                        <span class="error"> <?php echo $nameErr;?></span>
                    </form>
   <?php
  $id=(int)$salaryid;
   $_SESSION['LOAY']=$id;
    $workername="SELECT concat(firstname,'  ',lastname) as wholename FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
   $workeradress="SELECT address FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
   $workerZIP="SELECT zip FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
   $workerID="SELECT office_phone FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
   $paymenttype="SELECT concat(code,'  ',libelle) as wholename FROM `llx_c_paiement` WHERE id IN ( SELECT fk_typepayment FROM llx_payment_salary WHERE rowid =$id)";
   $hourewage="SELECT thm FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
   $houreperW="SELECT weeklyhours FROM `llx_user` WHERE rowid IN ( SELECT fk_user FROM llx_payment_salary WHERE rowid =$id)";
   $salaryamount="SELECT amount FROM llx_payment_salary where rowid =$id";
    $DATE="SELECT datep FROM llx_payment_salary where rowid =$id";


   ?>
                </div>
        <div CLASS="card-body">
            <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width=635
                   style='width:476.0pt;border-collapse:collapse;mso-yfti-tbllook:1184;
 mso-padding-alt:0in 5.4pt 0in 5.4pt'>
                <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:15.75pt'>
                    <td width=195 style='width:146.6pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:15.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'> <?php

                                    $result=$con->query($DATE);
                                    $row =$result->fetch_assoc();
                                    echo $row["datep"];
                                    ?>
</span></b></p>
                    </td>
                    <td width=146 nowrap align="right" style='width:109.8pt;padding:0in 5.4pt 0in 5.4pt;
  height:15.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:14.0pt;font-family:"Times New Roman",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>&nbsp;&nbsp;Voucher salary

                                       </span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:15.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;</span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;padding:0in 5.4pt 0in 5.4pt;
  height:15.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black'><o:p><?php
                                    echo  date("Y/m/d");
                                    echo "**";
                                    echo date("h:i");
                                    ?></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:1;height:13.5pt'>
                    <td width=195 nowrap style='width:146.6pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:2;height:13.5pt'>
                    <td width=195 nowrap style='width:146.6pt;border:solid #35629D 1.0pt;
  mso-border-top-alt:1.0pt;mso-border-left-alt:1.0pt;mso-border-bottom-alt:
  .5pt;mso-border-right-alt:.5pt;mso-border-color-alt:#35629D;mso-border-style-alt:
  solid;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>company<o:p></o:p></span></b></p>
                    </td>
                    <td width=439 nowrap colspan=3 style='width:329.4pt;border:solid #35629D 1.0pt;
  border-left:none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:
  solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:3;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Name<o:p></o:p></span></p>
                    </td>
                    <td width=439 colspan=3 style='width:329.4pt;border-top:none;border-left:
  none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:solid #35629D .5pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>
                                <?php
$result=$con->query($companyname);
$row =$result->fetch_assoc();
echo $row["value"];
?>


                                <o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:4;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Adress<o:p></o:p></span></p>
                    </td>
                    <td width=439 colspan=3 style='width:329.4pt;border-top:none;border-left:
  none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:solid #35629D .5pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'> <?php
                                $result=$con->query($companyaddress);
                                $row =$result->fetch_assoc();
                                echo $row["value"];
                                ?>
                                <o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:5;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Zip Code<o:p></o:p></span></p>
                    </td>
                    <td width=439 colspan=3 style='width:329.4pt;border-top:none;border-left:
  none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-top-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;height:
  13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><?php
                                $result=$con->query($companyzip);
                                $row =$result->fetch_assoc();
                                echo $row["value"];
                                ?>
                                <o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:6;height:13.5pt'>
                    <td width=195 nowrap style='width:146.6pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:7;height:12.75pt'>
                    <td width=195 nowrap style='width:146.6pt;border:solid #35629D 1.0pt;
  mso-border-top-alt:1.0pt;mso-border-left-alt:1.0pt;mso-border-bottom-alt:
  .5pt;mso-border-right-alt:.5pt;mso-border-color-alt:#35629D;mso-border-style-alt:
  solid;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>Workers<o:p></o:p></span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:solid #35629D 1.0pt;
  border-left:none;border-bottom:solid #35629D 1.0pt;border-right:none;
  mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:solid #35629D 1.0pt;
  border-left:none;border-bottom:solid #35629D 1.0pt;border-right:none;
  mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border:solid #35629D 1.0pt;
  border-left:none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:
  solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:8;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Name<o:p></o:p></span></p>
                    </td>
                    <td width=439 colspan=3 style='width:329.4pt;border-top:none;border-left:
  none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>
                                <?php

                                $result=$con->query($workername);
                                $row =$result->fetch_assoc();
                                echo $row["wholename"];
                                ?> <o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:9;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Adress<o:p></o:p></span></p>
                    </td>
                    <td width=439 colspan=3 style='width:329.4pt;border-top:none;border-left:
  none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:solid #35629D .5pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>
                                  <?php

                                  $result=$con->query($workeradress);
                                  $row =$result->fetch_assoc();
                                  echo $row["address"];
                                  ?><o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:10;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Zip Code<o:p></o:p></span></p>
                    </td>
                    <td width=439 colspan=3 style='width:329.4pt;border-top:none;border-left:
  none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:solid #35629D .5pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>
                                <?php

                                $result=$con->query($workerZIP);
                                $row =$result->fetch_assoc();
                                echo $row["zip"];
                                ?><o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:11;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:.5pt;
  mso-border-left-alt:1.0pt;mso-border-bottom-alt:1.0pt;mso-border-right-alt:
  .5pt;mso-border-color-alt:#35629D;mso-border-style-alt:solid;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>ID<o:p></o:p></span></p>
                    </td>
                    <td width=439 nowrap colspan=3 style='width:329.4pt;border-top:none;
  border-left:none;border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-top-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;height:
  13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'><?php

                                $result=$con->query($workerID);
                                $row =$result->fetch_assoc();
                                echo $row["office_phone"];
                                ?><o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:12;height:13.5pt'>
                    <td width=195 style='width:146.6pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>&nbsp;</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>&nbsp;</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>&nbsp;</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:#E26B0A'>&nbsp;<o:p></o:p></span></b></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:13;height:12.75pt'>
                    <td width=195 nowrap style='width:146.6pt;border:solid #35629D 1.0pt;
  mso-border-top-alt:1.0pt;mso-border-left-alt:1.0pt;mso-border-bottom-alt:
  .5pt;mso-border-right-alt:.5pt;mso-border-color-alt:#35629D;mso-border-style-alt:
  solid;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>Basics<o:p></o:p></span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:solid #35629D 1.0pt;
  border-left:none;border-bottom:solid #35629D 1.0pt;border-right:none;
  mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:solid #35629D 1.0pt;
  border-left:none;border-bottom:solid #35629D 1.0pt;border-right:none;
  mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border:solid #35629D 1.0pt;
  border-left:none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:
  solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:14;height:12.75pt'>
                    <td width=195 nowrap style='width:146.6pt;border:solid #35629D 1.0pt;
  border-top:none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:
  solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>City<o:p></o:p></span></p>
                    </td>
                    <td width=293 nowrap colspan=2 style='width:3.05in;border:none;border-bottom:
  solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<?php
                                $result=$con->query($companytown);
                                $row =$result->fetch_assoc();
                                echo $row["value"];
                                ?><o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:15;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Billing procedure<o:p></o:p></span></p>
                    </td>
                    <td width=293 nowrap colspan=2 style='width:3.05in;border:none;border-bottom:
  solid #35629D 1.0pt;mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:
  solid #35629D .5pt;mso-border-bottom-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp; <?php

                                $result=$con->query($paymenttype);
                                $row =$result->fetch_assoc();
                                echo $row["wholename"];
                                ?><o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:16;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Hourly wages<o:p></o:p></span></p>
                    </td>
                    <td width=293 nowrap colspan=2 style='width:3.05in;border:none;border-bottom:
  solid #35629D 1.0pt;mso-border-top-alt:solid #35629D .5pt;mso-border-top-alt:
  solid #35629D .5pt;mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal  style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black';><?php

                            $result=$con->query($hourewage);
                            $row =$result->fetch_assoc();
                            echo"$";
                            echo (float)$row["thm"];
                            ?>&nbsp;<o:p></o:p></span></p>
                    <td width=146  nowrap  style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt';>

                    </td>
                </tr>
                <tr style='mso-yfti-irow:17;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>number of hours per week<o:p></o:p></span></p>
                    </td>
                    <td width=293 nowrap colspan=2 style='width:3.05in;border:none;border-bottom:
  solid #35629D 1.0pt;mso-border-top-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'><?php

                                $result=$con->query($houreperW);
                                $row =$result->fetch_assoc();
                                echo (float)$row["weeklyhours"];
                                ?>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:18;height:13.5pt'>
                    <td width=195 nowrap style='width:146.6pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;background:white;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:19;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;mso-border-top-alt:
  1.0pt;mso-border-left-alt:1.0pt;mso-border-bottom-alt:.5pt;mso-border-right-alt:
  .5pt;mso-border-color-alt:#35629D;mso-border-style-alt:solid;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>wage</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border:solid #35629D 1.0pt;
  border-left:none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:
  solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span class=SpellE><b><span style='font-size:10.0pt;
  font-family:"Arial",sans-serif;mso-fareast-font-family:"Times New Roman";
  color:black;mso-color-alt:windowtext'>Ratio</span></b></span><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;background:#E6EAFE;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>Per
  hour</span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D 1.0pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>Per
  month</span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:20;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>salary<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'><o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'><?php

                        $result=$con->query($hourewage);
                        $row =$result->fetch_assoc();
                        echo"$";
                        echo (float)$row["thm"];
                        ?> </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'><?php

                        $result=$con->query($salaryamount);
                        $row =$result->fetch_assoc();
                        echo"$";
                        echo (float)$row["amount"];
                        ?></td>
                </tr>
                <tr style='mso-yfti-irow:21;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Holiday supplement<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>8.33%<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <?php
                        $result=$con->query($hourewage);
                        $row =$result->fetch_assoc();
                        echo"$";
                        $hourewageaf= (float)$row["thm"]*.0833;
                        echo $hourewageaf;
                        ?>                    </td>

                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'><?php

                        $result=$con->query($salaryamount);
                        $row =$result->fetch_assoc();
                        echo"$";
                        $salaryamountaf= (float)$row["amount"]*.0833;
                        echo $salaryamountaf;
                        ?></td>
                </tr>
                <tr style='mso-yfti-irow:22;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>Total</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D 1.0pt;mso-border-right-alt:solid #35629D .5pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D 1.0pt;mso-border-right-alt:solid #35629D .5pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <?php

                        $result=$con->query($hourewage);
                        $row =$result->fetch_assoc();
                        echo"$";
                        $hourwagetotal= (float)$row["thm"]+ $hourewageaf;
                        echo $hourwagetotal;
                        ?>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <?php

                        $result=$con->query($salaryamount);
                        $row =$result->fetch_assoc();
                        echo"$";
                        $slaryamounttotal=(float)$row["amount"]+$salaryamountaf;
                        echo $slaryamounttotal;
                        ?>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:23;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:none;border-bottom:solid #35629D 1.0pt;
  background:white;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>&nbsp;</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border:none;border-bottom:solid #35629D 1.0pt;
  background:white;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border:none;border-bottom:solid #35629D 1.0pt;
  background:white;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></b></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border:none;border-bottom:solid #35629D 1.0pt;
  background:white;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black'>&nbsp;<o:p></o:p></span></b></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:24;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;background:#E6EAFE;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>Tax</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  background:#E6EAFE;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span class=SpellE><b><span style='font-size:10.0pt;
  font-family:"Arial",sans-serif;mso-fareast-font-family:"Times New Roman";
  color:black;mso-color-alt:windowtext'>Ratio</span></b></span><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  background:#E6EAFE;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>Per
  hour</span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  background:#E6EAFE;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>Per
  month</span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:25;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>AHV/IV/EO<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>5.30%<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>
                                <?php

                        $result=$con->query($hourewage);
                        $row =$result->fetch_assoc();
                        echo"$";
                        $hourewagetaxAHV=((float)$row["thm"]+ $hourewageaf)*.053;
                        echo $hourewagetaxAHV;
                        ?>
                        <o:p></o:p></span></p>

                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'> <?php

                                $result=$con->query($salaryamount);
                                $row =$result->fetch_assoc();
                                echo"$";
                                $salaryamounttaxAHV= ((float)$row["amount"]+$salaryamountaf)*.053;
                                echo $salaryamounttaxAHV;
                                ?><o:p></o:p></span>

                        </p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:26;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>ALV<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>1.10%<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><?php

                                $result=$con->query($hourewage);
                                $row =$result->fetch_assoc();
                                echo"$";
                                $hourwagetaxalv = ((float)$row["thm"]+ $hourewageaf)*.011;
                                echo $hourwagetaxalv;
                                ?><o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>
                                <?php

                                $result=$con->query($salaryamount);
                                $row =$result->fetch_assoc();
                                echo"$";
                                $salaryamounttaxALV= ((float)$row["amount"]+$salaryamountaf)*.011;
                                echo $salaryamounttaxALV;
                                ?>
                                <o:p></o:p></span></p>

                    </td>
                </tr>
                <tr style='mso-yfti-irow:27;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>KTV<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:28;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>NBU<o:p></o:p></span></p>
                    </td>
                    <td width=146 nowrap style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>

                <tr style='mso-yfti-irow:30;height:12.75pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;border-top:
  none;mso-border-left-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D .5pt;
  mso-border-right-alt:solid #35629D .5pt;padding:0in 5.4pt 0in 5.4pt;
  height:12.75pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span
                                    style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'>Withholding Tax<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>&nbsp;<o:p></o:p></span></p>
                    </td>
                    <td width=146 style='width:109.8pt;border-top:none;border-left:none;
  border-bottom:solid #35629D 1.0pt;border-right:solid #35629D 1.0pt;
  mso-border-bottom-alt:solid #35629D .5pt;mso-border-right-alt:solid #35629D 1.0pt;
  padding:0in 5.4pt 0in 5.4pt;height:12.75pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'>&nbsp;<o:p></o:p></span></p>
                    </td>
                </tr>
                               <tr style='mso-yfti-irow:32;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;mso-border-top-alt:
  .5pt;mso-border-left-alt:1.0pt;mso-border-bottom-alt:1.0pt;mso-border-right-alt:
  .5pt;mso-border-color-alt:#35629D;mso-border-style-alt:solid;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>Total Tax</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D .5pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>6.40%</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D .5pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>
                                    <?php
                              $hourtax=$hourewagetaxAHV+$hourwagetaxalv;
                                    echo $hourtax;

                                    ?>

                                </span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D .5pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D 1.0pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'><?php
                                    $slarytax=$salaryamounttaxAHV+$salaryamounttaxALV;
                                    echo $slarytax;
                                    ?></span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                </tr>
                <tr style='mso-yfti-irow:33;height:13.5pt'>
                    <td width=195 style='width:146.6pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'></td>
                    <td width=146 style='width:109.8pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'></td>
                    <td width=146 style='width:109.8pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'></td>
                    <td width=146 style='width:109.8pt;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'></td>
                </tr>
                <tr style='mso-yfti-irow:34;mso-yfti-lastrow:yes;height:13.5pt'>
                    <td width=195 style='width:146.6pt;border:solid #35629D 1.0pt;mso-border-alt:
  solid #35629D 1.0pt;mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;
  padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>Net salary</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman";color:black;mso-color-alt:windowtext'>&nbsp;</span></b><b><span
                                        style='font-size:10.0pt;font-family:"Arial",sans-serif;mso-fareast-font-family:
  "Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;mso-border-top-alt:solid #35629D 1.0pt;mso-border-bottom-alt:solid #35629D 1.0pt;
  mso-border-right-alt:solid #35629D .5pt;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;
  height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>
                                    <?php
                                    echo $hourwagetotal-$hourtax;
                                    ?>
                                </span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                    <td width=146 style='width:109.8pt;border:solid #35629D 1.0pt;border-left:
  none;background:#DCE6F1;padding:0in 5.4pt 0in 5.4pt;height:13.5pt'>
                        <p class=MsoNormal align=right style='margin-bottom:0in;text-align:right;
  line-height:normal'><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman";color:black;mso-color-alt:windowtext'>
<?php
                                    echo $slaryamounttotal-$slarytax;
?>
                                </span></b><b><span style='font-size:10.0pt;font-family:"Arial",sans-serif;
  mso-fareast-font-family:"Times New Roman"'><o:p></o:p></span></b></p>
                    </td>
                </tr>
            </table>

        </div>
        </div>
    </div>
    </div>
</div>
</div>
<a href="pdf_gen.php" title="">go to another</a>
</body>
</html>