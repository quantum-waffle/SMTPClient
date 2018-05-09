<?php

// Initialize the session
session_start();

// If session variable is not set it will redirect to login page
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
  header("location: login.php");
  exit;
}

$msg_status = "";
$server_ip = "127.0.0.1";
$server_port = 666;

function SocketConnect($server_ip, $server_port){
	set_time_limit(5);
	 
	if (($socket = socket_create(AF_INET, SOCK_STREAM, 0)) === false) {
	    $msg_status = $msg_status . "Could not create socket\n";
	}else{
		$msg_status = $msg_status . "Socket created succesfuly!\n";
	}
	 
	if (($connection = socket_connect($socket, $server_ip, $server_port)) === false) {
	    $msg_status = $msg_status . "Could not connect to server\n";
	}else{
		$msg_status = $msg_status . "Succesfully connected!!\n";
        return $socket;
	}
}

function send($socket, $message){
    socket_write($socket, $message, strlen($message));
}

function receive($socket){
    if (($data = socket_read($socket, 1024)) === false) {
        $msg_status = $msg_status . "Could not read input\n";
        return 0;
    } else {
        //echo "Server sent:" . $data . "\n";
        return $data;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
	// validation expected data exists
    if(!isset($_POST['rcpt_to']) ||
        !isset($_POST['subject']) ||
        !isset($_POST['content'])) {
        $msg_status = $msg_status . "We are sorry, but there appears to be a problem with the form you submitted\n";       
    }
    $rcpt_to = $_POST['rcpt_to']; // required
    $subject = $_POST['subject']; // required
    $content = $_POST['content']; // required
    $mail_from = $_SESSION['username'];

    $rcpt_to_array = explode(",",$rcpt_to);

    // echo "From: "."$mail_from\n";
    // echo "To: "."$rcpt_to\n";
    // echo "Subject: "."$subject\n";
    // echo "Content: "."$content\n";
    
    $server_socket = SocketConnect($server_ip, $server_port);

    $data = receive($server_socket);
    if (strpos($data, '220') !== false) {
        //echo "Read 220\n";
        send($server_socket, "HELO");
        $data = receive($server_socket);
        if (strpos($data, '250') !== false){
            //echo "Read 250 HELO\n";
            send($server_socket, "MAIL FROM:".$mail_from);
            $data = receive($server_socket);
            if (strpos($data, '250') !== false){
                //echo "Read 250 MAIL FROM\n";
                for($x = 0; $x < count($rcpt_to_array); $x++) {
                    send($server_socket, "RCPT TO:".$rcpt_to_array[$x]);
                    $data = receive($server_socket);
                }
                if (strpos($data, '250') !== false){
                    //echo "Read 250 RCPT TO\n";
                    send($server_socket, "DATA");
                    $data = receive($server_socket);
                    if (strpos($data, '354') !== false){
                        //echo "Read 354 DATA";
                        $mail_content = $subject."\n".$content;
                        send($server_socket, $mail_content);
                        send($server_socket, ".\r\n");
                        $data = receive($server_socket);
                        if (strpos($data, '250') !== false){
                            //echo "Read 250 DONE!";
                            $msg_status = $msg_status . "SENT!";
                        }
                    }
                }
            }
        }
    }
    socket_close($server_socket);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send a Mail!</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; background-image: url("background.png");  background-color: #cccccc;}
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <p><a style="position: absolute; left: 85%; top: 1%; width: 10em;" href="logout.php" class="btn btn-danger">Log Out</a></p>
    <div class="wrapper" style="position:relative; top: 53%; left: 30%; margin-top: 4%; width: 500px;">
    	<h2 style="color: white; text-shadow:0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black, 0 0 4px black; font-weight: bold; text-align: center; margin-left: 130px;">Send a Mail</h2>
    	<p style="color: white; text-align: left">Please, separate mails with ",".</p>
    	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    	<table width="550px">
    	<tr>
    	 <td valign="top">
    	  <label style="color: white;" for="rcpt_to">To:</label>
    	 </td>
    	 <td valign="top">
    	  <input  class="form-control" type="text" name="rcpt_to" maxlength="50" size="30">
    	 </td>
    	</tr>
    	<tr>
    	 <td valign="top"">
    	  <label style="color: white;" for="subject">Subject:</label>
    	 </td>
    	 <td valign="top">
    	  <input  class="form-control" type="text" name="subject" maxlength="50" size="30">
    	 </td>
    	</tr>
    	<tr>
    	 <td valign="top">
    	  <label style="color: white;" for="content">Content:</label>
    	 </td>
    	 <td valign="top">
    	  <textarea  class="form-control" name="content" maxlength="1000" cols="25" rows="6"></textarea>
    	 </td>
    	</tr>
    	<tr>
    	 <td colspan="2" style="text-align:center">
    	  <input style="position: relative; left: 80px; width: 200px;" type="submit" class="btn btn-primary" value="Send Mail">
    	 </td>
    	</tr>
    	</table>
    	</form>
    	<span style="position: relative;left: 70%;font-weight: bold; color: #4cda4f;" class="text-success"><?php echo $msg_status; ?>   
        </span>
    </div>    
</body>
</html>