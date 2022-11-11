<?PHP

	$INFolder=__DIR__.'/../../IN/';
	$INBOX=__DIR__.'/../../INBOX/';
    $INBOXCache=__DIR__.'/../cache/INBOX.json';
	
    function createFTPLogin(){
        global $INFolder;
        if(!is_dir($INFolder)){
            if(mkdir($INFolder)){
                return true;
            }else{return 'Error, could not create directory';}
        }else{return true;}
    }
	
    function retrieveFTPLogin(){
        //static login pointing to /IN
        $FTPLogin=[];
        $FTPLogin['protocol']='ftp';
        $FTPLogin['server']='ftp.cluster010.hosting.ovh.net';
        $FTPLogin['port']='21';
        $FTPLogin['login']='edenuniv-benjamin';
        $FTPLogin['password']='butune49NOLO';
        $FTPLogin['path']='/';
        return $FTPLogin;
    }

    function removeFTPLogin(){
        global $INFolder;
        if(is_dir($INFolder)){$result=rmdir($INFolder);}else{$result=true;}
        if($result){
            if($result){
                return true;
            }else{return 'Error, could not delete symlink';}
        }else{return 'Error, could not delete directory';}
    }
    
?>
