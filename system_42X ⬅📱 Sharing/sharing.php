<?php
    // This most important plugin lets you share file to PDM
    // over any file-transfert protocol such as FTP or SFTP
    //**************************************************************
    $settings_default='sharing_settings.php';
    $clientOff='sharing_clientOff.html';
    $clientOn='sharing_clientOn.html';
    $daemonOff='sharing_daemonOff.php';
    $settingsTemplate='<?PHP
    // An folder dedicated to recieve files sent by FTP
    // If missing, it should be permanently existing or created by function createFTPLogin() 
    $INFolder=__DIR__."/IN";
    $symlink=__DIR__."/../../IN";
	$INBOX=__DIR__."/../../INBOX/";
    
    // createFTPLogin()
    // This function creates an service that allows depositing files in the $INFolder
    // and return true on success or a string on failure.
    // Note: the FTP login name can be fixed or based on the system name: "/SETTINGS_name.txt"
    // Note: the FTP login password is to be generated with a pseudo-random algorythm (not by the user)
    // --> this is not a realPB as it is the kind of information set ONCE on a mobile phone (or the desktop with online folder)
    // --> so not a big deal if complex (even good for safety)
    // NOTE: This example uses a persistant login created by the admin once in for all by the admin
    // NOTE: So all I do is simulate create / deletion of the login, but it never changes
    function createFTPLogin(){
        global $INFolder;
        global $symlink;
        if(!is_dir($INFolder)){
            if(mkdir($INFolder)){
                if(file_exists($symlink)){unlink($symlink);}
                if(symlink($INFolder,$symlink)){
                    return true;
                }else{return "Error, could not create symlink";}
            }else{return "Error, could not create directory";}
        }else{return true;}
    }
    
    // retrieveFTPLogin()
    // This function returns the array of information related to the service, so the user can connect
    // Information is: Protocol, Server, Port, Login, Password, Path
    // NOTE: This example uses a persistant login created by the admin once in for all by the admin
    // NOTE: So all I do is simulate create / deletion of the login, but it never changes
    function retrieveFTPLogin(){
        $FTPLogin=[];
        $FTPLogin["protocol"]="ftp";
        $FTPLogin["server"]="ftp.cluster010.hosting.ovh.net";
        $FTPLogin["port"]="21";
        $FTPLogin["login"]="user";
        $FTPLogin["password"]="password";
        $FTPLogin["path"]="/";
        return $FTPLogin;
    }

    // removeFTPLogin()
    // This function removes the service
    // and return true on success or a string on failure.
    // NOTE: This example uses a persistant login created by the admin once in for all by the admin
    // NOTE: So all I do is simulate create / deletion of the login, but it never changes
    function removeFTPLogin(){
        global $INFolder;
        global $symlink;
        if(is_dir($INFolder)){$result=rmdir($INFolder);}else{$result=true;}
        if($result){
            if(file_exists($symlink)){$result=unlink($symlink);}else{$result=true;}
            if($result){
                return true;
            }else{return "Error, could not delete symlink";}
        }else{return "Error, could not delete directory";}
    }
?>
';
    //**************************************************************
    if(file_exists('./settings.php')){
        include('./settings.php');
        if(!isset($_GET['action'])){
            if(file_exists('daemon.php')){
                echo file_get_contents($clientOn);
            }else{
                echo file_get_contents($clientOff);
            }
        }
        elseif($_GET['action']=='startDaemon'){
            $res=createFTPLogin();
            if($res===true){
                if(copy($daemonOff,'daemon.php')){
                    header('Location: '.$_SERVER['PHP_SELF']);
                }else{echo "Error: could not create daemon file";}
            }else{echo "Error createFTPLogin(): ".$res;}
        }
        elseif($_GET['action']=='retrieveLogin'){
            echo json_encode(retrieveFTPLogin());
        }
        elseif($_GET['action']=='stopDaemon'){
            if(unlink('daemon.php')){
                $res=removeFTPLogin();
                if($res===true){
                    header('Location: '.$_SERVER['PHP_SELF']);
                }else{echo "Error from removeFTPLogin(): ".$res;}
            }else{echo "Error: could not remove daemon file";}
        }
        else{echo 'Error: Unknown action';}
    }
    else{
        echo '<h1>⚠ Plugin not configured</h1>';
        echo '<p>This plugin requires server-level configuration</p>';
        echo '<p>To do this, the server admin should access the plugin folder, and create a suitable "settings.php" file.</p>';
        echo '<p>Hereunder is a template content:</p>';
        echo '<p>***************************************************************************</p>';
        echo '<code><pre>';
        echo htmlentities($settingsTemplate);
        echo '</pre></code>';
        echo '<p>***************************************************************************</p>';
    }
?>
 
