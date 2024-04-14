<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// require 'PHPMailer-master/src/Exception.php';
// require 'PHPMailer-master/src/PHPMailer.php';
// require 'PHPMailer-master/src/SMTP.php';

require 'vendor/autoload.php'; // Path to the Composer autoload file

function sendEmail($emailAddress, $fullname, $seatNumber, $seatAmount, $donateAmount, $total,  $total_received_amount, $message, $balance, $isError){
    if ($donateAmount === ""){
        $donateAmount = "0";
    }
    $refund_message = "";
    
    // $mail = new PHPMailer(true);
    $mail = new PHPMailer(); $mail->IsSMTP(); $mail->Mailer = "smtp";

    // $mail->SMTPDebug  = 1;  
    $mail->SMTPAuth   = TRUE;
    $mail->SMTPSecure = "tls";
    $mail->Port       = 587;
    $mail->Host       = "smtp.gmail.com";
    $mail->Username   = "pemantausistem@gmail.com";
    $mail->Password   = "tzkskagrzuwwnllc";
    $mail->isHTML(true);

    try {
        $mail->setFrom('pemantausistem@gmail.com', 'Tigaky'); // Replace with your email and name
        $mail->addAddress($emailAddress, $fullname); // Replace with the recipient's email and name
        $mail->addCC('chamzhaosi0808@e.newera.edu.my'); // Add CC recipient
        // $mail->Subject = 'Order Confirmation';
        // $mail->Body    = 'This is your order confirmation email. Thank you for your purchase!';

        if(!$isError){
            if ($message != "Match, but payment more"){
                $mail->Subject = 'Order Confirmation';
            }else{
                $mail->Subject = 'Order Confirmation and Refund';
                $refund_message = '
                    Also, We have detected an <b> overpayment of RM '. htmlspecialchars($balance) .'</b> in your recent transaction. Please contact us soon to arrange a prompt refund of this amount.
                    ';
            }

            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    .email-content {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                    }
                </style>
            </head>
            <body>
                <div class="email-content">
                    <p>Dear ' . htmlspecialchars($fullname) . ',</p>
                    <p>We would like to extend our heartfelt thanks for your recent booking with us. Below are the details of your booking:</p>
                    
                    <p><strong>Seat Number:</strong> ' . htmlspecialchars($seatNumber) . '</p>
                    <p><strong>Seat Amount:</strong> RM ' . htmlspecialchars($seatAmount) . '</p>
                    <p><strong>Donate Amount:</strong> RM ' . htmlspecialchars($donateAmount) . '</p>
                    <p><strong>Total Amount:</strong> RM ' . htmlspecialchars($total) . '</p>
                    
                    <p>'. htmlspecialchars($refund_message) .'</p>

                    <p>If you have any questions or require further assistance, please feel free to contact us <b> 012-1234567 and ask for Mr. Jacky </b> for assistance. We look forward to welcoming you and hope you enjoy your experience.</p>
            
                    <p>Best Regards,</p>
                    <p> Medical Awareness Camp Outreach (MACO) </p>
                </div>
            </body>
            </html>
            ';
        }else if($isError && $message === "Match, but payment less"){

            $mail->Subject = 'Underpayment - Refund notification';
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    .email-content {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                    }
                </style>
            </head>
            <body>
                <div class=\"email-content\">
                    <p>Dear ' . htmlspecialchars($fullname) . ',</p>
                    <p>We hope this message finds you well. We are reaching out to inform you that there has been an <b> underpayment of RM ' . htmlspecialchars($balance) . ' </b> in your recent transaction with us. We understand that such occurrences can happen and would like to assure you that we are here to support you in resolving this matter.</p>
                    
                    <p>To streamline the process and ensure accuracy, we would like to propose a <b> refund of the paid amount (RM'.htmlspecialchars($total_received_amount).'). </b> This will enable you to make a new transaction with the correct amount. We believe this approach is the most straightforward and efficient way to rectify the situation.</p>

                    <p>Please contact us at your earliest convenience to initiate the refund process. We are committed to providing you with the necessary assistance to ensure a smooth and hassle-free experience.</p>

                    <p>Should you have any questions or require further clarification, please do not hesitate contact us at <b> 012-1234567 and ask for Mr. Jacky </b> for assistance. We apologize for any inconvenience this may cause and thank you for your understanding and cooperation.</p>

                    <p>Warm Regards,</p>
                    <p> Medical Awareness Camp Outreach (MACO) </p>
                </div>
            </body>
            </html>
            ';
        }else if($isError && $message === "Cancel by user, but got transaction"){

            $mail->Subject = 'Order Cancellation - Refund notification';
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    .email-content {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                    }
                </style>
            </head>
            <body>
            <div class="email-content">
                <p>Dear '. htmlspecialchars($fullname).',</p>
                <p>We hope this message finds you well. We are writing to inform you that although your order was cancelled, we have detected a transaction of <b>RM '. htmlspecialchars($total_received_amount) .'</b> that has already been processed to our bank account.</p>
                
                <p>To ensure a smooth resolution, we would like to assist you in processing a <b>refund of the amount received (RM '. htmlspecialchars($total_received_amount) .')</b>. We apologize for any inconvenience this may have caused and would like to make the refund process as straightforward and efficient as possible for you.</p>
            
                <p>Please contact us at <b>012-1234567 and ask for Mr. Jacky</b> at your earliest convenience to begin the refund process. Our team is ready to provide you with the necessary assistance to ensure a hassle-free experience.</p>
            
                <p>If you have any questions or need further clarification, please feel free to reach out to us. We thank you for your understanding and cooperation in this matter.</p>
            
                <p>Warm Regards,</p>
                <p>Medical Awareness Camp Outreach (MACO)</p>
            </div>
            </body>
            </html>
            ';
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
        // return 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;
    }

}
?>
