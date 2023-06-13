<?php
// Include required PHPMailer files
require 'C:\xampp\htdocs\ensajconf\wp-includes\PHPMailer\PHPMailer.php';
require 'C:\xampp\htdocs\ensajconf\wp-includes\PHPMailer\SMTP.php';
require 'C:\xampp\htdocs\ensajconf\wp-includes\PHPMailer\Exception.php';
// Define namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
function sendMail($title, $receiver, $username, $password, $url)
{
	// Create instance of PHPMailer
	$mail = new PHPMailer();
	// Set mailer to use SMTP
	$mail->isSMTP();
	// Define SMTP host
	$mail->Host = "smtp.gmail.com";
	// Enable SMTP authentication
	$mail->SMTPAuth = true;
	// Set SMTP encryption type (ssl/tls)
	$mail->SMTPSecure = "tls";
	// Port to connect SMTP
	$mail->Port = "587";
	// Set Gmail username
	$mail->Username = "mouridyahya380@gmail.com";
	// Set Gmail password
	$mail->Password = "fpzkjjoyjjzgmsbk";
	// Set email subject
	$mail->Subject = "Conference creation";
	// Set sender email
	$mail->setFrom('mouridyahya380@gmail.com');
	// Enable HTML
	$mail->isHTML(true);
	// Email body
	$mail->Body = '<html>
	<head>
	  <style>
		.email-body {
		  font-family: Arial, sans-serif;
		  font-size: 16px;
		  background-color: #abdbe3;
		  line-height: 1.5;
		  color: #333333;
		  padding: 20px;
		  max-width: 600px;
		  margin: 0 auto;
		  box-shadow: 0 8px 8px #7E7B7B;
		  border-radius: 10px;
		}
		h1 {
		  font-size: 28px;
		  color: black;
		  margin-bottom: 20px;
		  text-align: center;
		}
		p {
		  margin-bottom: 10px;
		  font-size: 18px;
		}
		a {
		  color: #006699;
		  text-decoration: underline;
		}
	  </style>
	</head>
	<body>
	  <div class="email-body">
		<h1>Your conference <strong>'.$title.'</strong> is successfully created</h1>
		<p>Thank you for using our service to create your conference. Your web site is 
		ready to use.Please find your Wordpress login details below:</p>
		<p><strong>Your web site url:    </strong> 
		<a href="'.$url.'" target="_blank">'.$url.'</a></p>
		<p>Click on this link to personalize your conference:</p>
		<p><a href="'.$url.'/wp-admin" target="_blank">'.$url.'/wp-admin</a></p>
		<p>Please find your Wordpress login details below:</p>
		<p><strong>Username:    </strong> '.$username.'</p>
		<p><strong>Password:    </strong> '.$password.'</p>	
	  </div>
	</body>
	</html>';
	// Add recipient
	$mail->addAddress($receiver);
	// Finally send email
	$mail->send();
}
