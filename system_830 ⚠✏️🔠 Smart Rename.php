<?php
    session_start(['cookie_lifetime' => 86400]);
    if(!isset($_SESSION['loggedIn'])){
        header("Location: index.php");
        exit;
    }
    else{
        session_write_close();
        chdir(basename(__FILE__, '.php'));
        require_once('index.php');
    }
?>
