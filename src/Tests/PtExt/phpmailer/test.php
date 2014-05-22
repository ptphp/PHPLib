<?php
@header("content-Type: text/html; charset=utf-8"); //语言强制
include 'PtExt/phpmailer/class.phpmailer.php';
function send_mail($email,$body,$subject){

	$mail = new PHPMailer;

	$mail->IsSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'smtp.163.com';  // Specify main and backup server
	$mail->SMTPAuth = TRUE;                               // Enable SMTP authentication
	$mail->Username = '';                            // SMTP username
	$mail->Password = '';                           // SMTP password
	#$mail->SMTPSecure = 'ssl';                            // Enable encryption, 'ssl' also accepted

	$mail->From = 'dholer@163.com';
	$mail->FromName = '战神卫士110';
	$mail->AddAddress($email);               // Name is optional

	$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
	$mail->SMTPDebug = 2;
	$mail->IsHTML(true);                                  // Set email format to HTML
	if($subject){
		$mail->Subject =$subject;
	}else{
		$mail->Subject = '战神网络用户信息';
	}

	$mail->Body    = $body;
	//echo $body;
	if(!$mail->Send()) {
		echo 'Mailer Error: ' . $mail->ErrorInfo;
		return FALSE;
	}
	return TRUE;

}
if(send_mail("joseph@ptphp.com","body","subject")){
	echo "ok";
}
echo "<hr />";
highlight_file(__FILE__);