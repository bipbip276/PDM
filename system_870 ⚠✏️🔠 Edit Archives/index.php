<?php
    $datadir='../ARCHIVES/';
    $datadir_cache=$datadir.'.cache.json';
    //********************************************************************************
    // Developpment settings / No need to touch
    // ini_set('display_errors','On');            // to be clean, can be commented
    // date_default_timezone_set('Europe/Paris'); // not important, just to avoid warnings
    //********************************************************************************

    if(!isset($_GET['action'])){
        echo file_get_contents('client.html');
    }
    elseif($_GET['action']=='load'){
        echo get_archives();
    }
	elseif($_GET['action']=='edit_rename'){
		//mandatory: $_GET['sourceReference']
		//mandatory: $_GET['destinationReference']
		$source=$datadir.$_GET['sourceReference'];
		$destination=$datadir.$_GET['destinationReference'];
        if(file_exists($datadir_cache)){while(!unlink($datadir_cache)){sleep(1);}}
		if(isValidArchivesSourceReference($source)){
		  if(isValidArchivesDestinationReference($destination)){
			if(rename($source,$destination)){
				echo 'ok file renamed.';
			}else{echo 'Error: could rename file ';}
		  }else{echo 'Error: destination file already exists.';}
		}else{echo 'Error: source file does not exist.';}
	}
	elseif($_GET['action']=='edit_delete'){
		//mandatory: $_GET['sourceReference']
		$source=$datadir.$_GET['sourceReference'];
        if(file_exists($datadir_cache)){while(!unlink($datadir_cache)){sleep(1);}}
		if(isValidArchivesSourceReference($source)){
			if(unlink($source)){
                echo 'ok file deleted.';
			}else{echo 'Error: could delete file ';}
		}else{echo 'Error: source file does not exist.';}
	}
    elseif($_GET['action']=='archives_get'){
        #mandatory: $_GET['sourceReference']
        $sourceReference=$_GET['sourceReference'];
        if(isValidArchivesSourceReference($sourceReference)){
            header('Content-Type: '.mime_content_type($datadir.$sourceReference));
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length: ".filesize($datadir.$sourceReference));
            $MSTimestamp=substr($sourceReference,0,strpos($sourceReference," "));
            $STimestamp=floor(floatval($MSTimestamp)/1000);
            $stringDate=date('Y-m-d H:i:s',$STimestamp).'.'.substr($MSTimestamp,-3);
            $stringName=substr($sourceReference,15);
            header('Content-Disposition:; filename="'.$stringDate.' '.$stringName.'"');
            fpassthru(fopen($datadir.$sourceReference,'rb'));
        }else{echo 'Error, sourcePath is not valid';return false;}
    }
    else{echo "error unknown action: ".$_GET['action'];}

    // LIBRARIES
    // Specific User DATA files manipulations - top level
    function isValidArchivesSourceReference($source){
        if(gettype($source==="string")){
            $reference=basename($source);
            if($reference !==''){
                if($reference[0]!=='.'){
                    if(file_exists($source)){
                        return true;
                    }else{echo 'Error server: source reference ('.$datadir.$sourceReference.') file does not exist';return false;}
                }else{echo 'Error server: source reference cannot be a system file';return false;}
            }else{echo 'Error server: source reference file cannot be empty';return false;}
        }else{echo 'Error server: source reference incorrect or not provided';return false;}
    }
    function isValidArchivesDestinationReference($destination){
        if(gettype($destination==="string")){
            $reference=basename($destination);
            if(check_utf8($reference)){
                if(preg_match ('/^\d{14}\ [^\/]{1,240}$/',$reference)===1){
                    if(!file_exists($destination)){
                        return true;
                    }else{echo 'Error server: a file with the same name already exists';return false;}
                }else{echo 'Error server: destination reference is not correct (max length: 255, max timestamp 99999999999999, no "/" character): '.$destinationReference;return false;}
            }else{echo 'Error server: destination reference is not a well-formed UTF8 string';return false;}
        }else{echo 'Error server: destination incorrect or not provided';return false;}
    }
    // Other specific functions
    function get_archives(){
        global $datadir;
        global $datadir_cache;
        if(!file_exists($datadir_cache)){
            $cache=array();
            chdir(__DIR__.'/'.$datadir);
            foreach(glob('*',GLOB_NOSORT) as $filename){$cache[] = array('reference'=>$filename,'size'=>filesize($filename));}
            chdir(__DIR__);
            usort($cache,function($a,$b){return strnatcmp($b['reference'],$a['reference']);});
            $cache=json_encode($cache);
            file_put_contents($datadir_cache,$cache);
			return $cache;
        }
        else{
            return file_get_contents($datadir_cache);
        }
    }
    // Generic functions
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
?>
