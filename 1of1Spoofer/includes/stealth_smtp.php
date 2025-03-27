<?php function enhanceSpoofedEmail($mail, $fromEmail, $fromName, $logFile = null) { $mail->XMailer = ""; $mail->Priority = 3; return $mail; }
