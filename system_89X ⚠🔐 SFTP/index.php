<?php
	// ‼ SUPER function
	// ****************
	// This plugin implements a SUPER function (it does not respect PDM conventions)
	// Detail: it will allow the user to modify files in the ARCHIVES folder
	// To Enable this plugin: Rename the plugin folder. (Folders starting with a '.' are ignored)

	// Draft / Obsolete
	// ****************
	// The client.html file need to be rewritten
	// It is has been and just won't work as is
	
	// Admin CONFIGURATION needed
	// **************************
	function activate(){
		// this function should activate a 'root' access and return credential details
		// note a fix login and randomly generated password is fine or even good
		return array(
			'protocol'=>'ssh',
			'address'=>'ssh.domain.net',
			'port'=>22,
			'path'=>'/',
			'login'=>'user',
			'password'=>'password'
		);
	}
	function test(){
		// return true if activated, false otherwise
		return true;
	}
	function desactivate(){
		// this function should desactivate the root access
		// and return true on success or an error string otherwise
		return true;
	}
	
	// ***********************************************
	// ***********************************************
	// ***********************************************


	if(!isset($_GET['action'])){
		echo file_get_contents("client.html");
	}
	elseif($_GET['action']=='getPassword'){
		echo activate();
	}
	elseif($_GET['action']=='desactivate'){
		echo desactivate();
	}
	else{
		echo 'Unknown action';
	}

?>
