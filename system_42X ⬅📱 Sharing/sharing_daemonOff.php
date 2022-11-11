<?PHP
	$settingsFile='settings.php';
    //****************************************
	if(file_exists($settingsFile)){
		require_once($settingsFile);
		chdir(dirname(__FILE__));
		$files=scandir($INFolder);
		$newfile=false;
		foreach($files as $filename){
			if($filename[0]!='.' && end(explode('.',$filename))!='part'){
				$newfile=true;
				$sourcePath=$INFolder.$filename;
				if(!check_utf8($filename)){$filename='(!) Wong encoding filename';}
				if(strlen($filename)>240){$filename=substr($filename,0,237).'...';}
				$destinationPath=$INBOX.get_timestamp().' '.$filename;
				while(file_exists($destinationPath)){sleep(0.001);$destinationPath=$INBOX.get_timestamp().' '.$filename;}
				if(!rename($sourcePath,$destinationPath)){echo "Sharing plugin error: could not move file '".$sourcePath." to INBOX as '".$destinationPath."'";exit();}
			}//else just ignore it
		}
		if($newfile!==false){
			if(file_exists($INBOXCache)){
				if(unlink($INBOXCache)){echo "0";}
				else{echo "Sharing plugin error: could not unlink cache";}
			}else{echo "0";}
		}else{echo "0";}
	}else{unlink(__FILE__);echo "0";}
	

    function check_utf8($str) {
        $len = strlen($str);
        for($i = 0; $i < $len; $i++){
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c > 247)) return false;
                elseif ($c > 239) $bytes = 4;
                elseif ($c > 223) $bytes = 3;
                elseif ($c > 191) $bytes = 2;
                else return false;
                if (($i + $bytes) > $len) return false;
                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bytes--;
                }
            }
        }
        return true;
    }
    function get_timestamp(){
        return round(microtime(true)*1000);
    }
?>
