<?php
namespace providers;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Ubiquity\controllers\Startup;

class EmailProvider{
	
	/**
	 * Sends an e-mail, on failure will throw and exception
	 *
	 * @param string $toEmail - E-mai to send to
	 * @param string $toName - Name of the person you're e-mailing
	 * @param string $subject - Subject to use
	 * @param string $body - E-mail body, if HTML is provided and $plainTextBody is empty, tags will be stripped
	 * @param string $plainTextBody - Alternative body to send
	 * @param null $fromEmail - if different from the default
	 * @param null $fromName - if different from the default
	 * @throws \Exception
	 */
	public static function send($toEmail='', $toName='', $subject='', $body='', $plainTextBody=null, $fromEmail=null, $fromName=null){
		
		$debugMode = Startup::$config['providers']['email']['debug'];
		
		$host = Startup::$config['providers']['email']['host'];
		$username = Startup::$config['providers']['email']['username'];
		$password = Startup::$config['providers']['email']['password'];
		
		if(empty($fromEmail)) $fromEmail = Startup::$config['providers']['email']['from'];
		if(empty($fromName)) $fromName = Startup::$config['providers']['email']['fromName'];
		
		if(empty($toEmail)) throw new \Exception('Invalid E-mail provided');
		if(empty($toName)) throw new \Exception('Invalid Name');
		
		$mail = new PHPMailer(true);
		try {
			//Server settings
			$mail->SMTPDebug = ($debugMode ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF);                      // Enable verbose debug output
			
			$mail->isSMTP();
			$mail->Host       = $host;
			$mail->SMTPAuth   = true;
			$mail->Username   = $username;
			$mail->Password   = $password;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port       = 587;
			
			//Recipients
			$mail->setFrom($fromEmail, $fromName);
			$mail->addAddress($toEmail, $toName);
			
			$mail->Subject = $subject;
			$mail->Body    = $body;
			
			$strippedBody = (!empty($plainTextBody) ? $plainTextBody : strip_tags($body));
			if($strippedBody !== $body){
				$mail->isHTML(true);
				$mail->AltBody = $strippedBody;
			}
			
			$mail->send();
			
		} catch (Exception $e) {
			throw new \Exception($mail->ErrorInfo);
		}
	
	}
	
}
