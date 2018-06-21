<?php 
/*Helper Functions*/
function clean($string){
	return htmlentities($string);
}

function redirect($location){
	return header("Location: {$location}"); 
}

function set_message($message){
	if(!empty($message)){
		$_SESSION['message']=$message;
	} else{
		$message="";
	}
}

function display_message(){
	if(isset($_SESSION['message'])){
		echo $_SESSION['message'];
		unset($_SESSION['message']);
	}

}

function token_generator(){

	$token= $_SESSION['token']= md5(uniqid(mt_rand(),true));
	return $token;
}

function validation_errors($error_message){
	$error_message=<<<DELIMITER
<div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Warning!</strong> $error_message
</div>
DELIMITER;
	return $error_message;

}

function email_exists($email){
			$sql="SELECT id from users WHERE email='$email'";
			$result=query($sql);
			if(row_count($result)==1){
				return true;
			} else{
				return false;
			}

		}

function username_exists($username){
	$sql="SELECT id from users WHERE username='$username'";
	$result=query($sql);
	if(row_count($result)==1){
		return true;
	} else{
		return false;
	}

}

function send_email($email,$subject,$message,$headers){
 return mail($email,$subject,$message,$headers);
}

/*Validation Function*/

function validate_user_registration(){
	//echo "It works";
	$errors=[];
	$min=3;
	$max=20;
	if($_SERVER['REQUEST_METHOD']=="POST"){
		$first_name=		clean($_POST['first_name']);
		$last_name=			clean($_POST['last_name']);
		$username=			clean($_POST['username']);
		$email=				clean($_POST['email']);
		$password=			clean($_POST['password']);
		$confirm_password=	clean($_POST['confirm_password']);
		if(strlen($first_name)<$min){
			$errors[]="Your First Name cannot be less than {$min} characters";

		}
		if(strlen($first_name)>$max){
			$errors[]="Your First Name cannot be more than {$max} characters";

		}
		if(strlen($last_name)<$min){
			$errors[]="Your Last Name cannot be less than {$min} characters";

		}
		if(strlen($last_name)>$max){
			$errors[]="Your Last Name cannot be more than {$max} characters";

		}
		if(strlen($email)>$max){
			$errors[]="Your email cannot be more than {$max} characters";
		}
		if(strlen($email)<$min){
			$errors[]="Your email cannot be less than {$min} characters";
		}
		if($password!==$confirm_password){
			$errors[]="Your password fields do not match";
		}
		
		if(strlen($username)<$min){
			$errors[]="Your username cannot be less than {$min} characters";

		}
		if(strlen($username)>$max){
			$errors[]="Your username cannot be more than {$max} characters";

		}

		if(username_exists($username)){
			$errors[]="Sorry! that username already is taken";
		}

		if(email_exists($email)){
			$errors[]="Sorry! that email already is registered";
		}

		if(!empty($errors)){
			foreach ($errors as $error) {
				echo validation_errors($error);
				

			}
		} 
		else{
			if(register_user($first_name,$last_name,$username,$email,$password)){
				set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation</p>");
				redirect("index.php");
			}
			else{
				set_message("<p class='bg-danger text-center'>Sorry! We couldn't register the user</p>");
				redirect("index.php");
			}
		}
		
	} //post validation
} //function

/*Register Functions*/

function register_user($first_name,$last_name,$username,$email,$password){
	$first_name	=escape($first_name);
	$last_name	=escape($last_name);
	$username 	=escape($username);
	$email 		=escape($email);
	$password 	=escape($password);
	if(email_exists($email)){
		return false;
	} 
	else if(username_exists($username)){
		return false;
	} 
	else{
		$validation_code=md5($username+microtime());
		$password=md5($password);
		$sql="INSERT INTO users (first_name,last_name,username,email,password,validation_code,active)";
		$sql.=" VALUES('$first_name','$last_name','$username','$email','$password','$validation_code',0)"; 
		$result=query($sql);
		confirm($result);
		$subject="Activate Account";
		$message="Please click the link below to activate your Account
		http://localhost/login/activate.php?email=$email&code=$validation_code";
		$headers="From: noreply@example.com";
		send_email($email,$subject,$message,$headers);
		return true;
	}

}


/*Activate User Functions*/

function activate_user(){
	if($_SERVER['REQUEST_METHOD']=="GET"){
		if(isset($_GET['email'])){
			$email=clean($_GET['email']);
			$validation_code=clean($_GET['code']);
			$sql="SELECT id FROM users WHERE email='".escape($_GET['email'])."' AND validation_code='".escape($_GET['code'])."' ";
			$result=query($sql);
			//confirm($result);

			if(row_count($result)==1){
				$sql2="UPDATE users SET active=1, validation_code=0 WHERE email='".escape($email)."' AND validation_code='".escape($validation_code)."' ";
				$result2=query($sql2);
				confirm($result2);

				set_message("<p class='bg-success'>Your account has been activated, please Login</p>");
				redirect("login.php");
			}
			else{
				set_message("<p class='bg-success'>Sorry! Your account cannot be activated");
				redirect("login.php");	
			}
		}

	}
} //function

/*Validation User Login Functions*/

function validate_user_login(){
	//echo "It works";
	$errors=[];
	$min=3;
	$max=20;
	if($_SERVER['REQUEST_METHOD']=="POST"){
		$email=		clean($_POST['email']);
		$password=	clean($_POST['password']);
		$remember=	isset($_POST['remember']);
		if(empty($email)){
			$errors[]="Email field cannot be empty";
		}
		if(empty($password)){
			$errors[]="Password field cannot be empty";
		}



		if(!empty($errors)){
			foreach ($errors as $error) {
				echo validation_errors($error);
			}
		}
		else{
			if(login_user($email,$password,$remember)){
				redirect("admin.php");
			}
			else{
				echo validation_errors("Your credentials are not correct");
			}
		}
		
	}
}

/*User Login Functions*/

function login_user($email,$password,$remember){
	$sql="SELECT password, id FROM users WHERE email='".escape($email)."' AND active=1";
	$result=query($sql);
	if(row_count($result)==1){
		$row=fetch_array($result);
		$db_password=$row['password'];
		if(md5($password)==$db_password){
			if($remember=="on"){
				setcookie('email',$email,time()+600);
			}

			$_SESSION['email']=$email;
			return true;
		}
		else{
			return false;
		}
	}
	else{
		return false;
	}

} //end of function

/*Logged in Function*/
function logged_in(){
	if(isset($_SESSION['email']) || isset($_COOIKE['email'])){
		return true;
	}
	else{
		return false;
	}
} //end function

/*Forgot Password*/

function recover_password(){
	if($_SERVER['REQUEST_METHOD']=="POST"){
		if(isset($_SESSION['token']) && $_POST['token'] == $_SESSION['token'])
		{
			$email=clean($_POST['email']);

			if(email_exists($_POST['email'])){
				
				$validation_code=md5($email+microtime());

				setcookie('temp_access_code',$validation_code,time()+600); 

				$sql="UPDATE users SET validation_code='".escape($validation_code)."' WHERE email='".escape($email)."' ";
				$result=query($sql);
				confirm($result);
				$subject="Please reset your password";
				$message="Here is your password reset {validaton_code}
				Click here to reset your password http://localhost/login/code.php?email=$email&code=$validation_code

				";
				$headers="From: noreply@example.com";
			

				if(!send_email($email,$subject,$message,$headers)){
					echo validation_errors("Email couldn't be sent");
				}
				set_message("<p class='bg-success text-center'>Please check your email or spam folder for a password reset code.</p>");

				redirect("index.php");
				
			}
			else{
			echo validation_errors("This email doesn't exist"); 
			}
		}
	}
} //post request

/* Code Validation*/

function validate_code(){
	if(isset($_COOIKE['temp_access_code'])){
		if(!isset($_GET['email'])&& !isset($_GET['code'])){
			redirect("index.php");
		}
		elseif (empty($_GET['email'])||empty($_GET['code'])) {
			redirect("index.php");
		}
		else{
			if(isset($_POST['code'])){
				echo "getting post from form";
			}




		}









	}else{
		set_message("<p class='bg-danger text-center'>Sorry your validation cookie was expired");
		redirect("recover.php");
	}
	
}
?>