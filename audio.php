<?php
require 'google_speech.php';


//get the data from the post
$recordingURL = $_POST['RecordingUrl'];



//send by email

$from_add = "gideon@calloutsapp.com"; 

	$to_add = "gideonm@gmail.com"; //<-- put your yahoo/gmail email address here

	$subject = "Test Subject";
	$message = $recordingURL;
	
	$headers = "From: $from_add \r\n";
	$headers .= "Reply-To: $from_add \r\n";
	$headers .= "Return-Path: $from_add\r\n";
	$headers .= "X-Mailer: PHP \r\n";
	
	
	if(mail($to_add,$subject,$message,$headers)) 
	{
		$msg = "Mail sent OK";
	} 
	else 
	{
 	   $msg = "Error sending email!";
	}


?>
