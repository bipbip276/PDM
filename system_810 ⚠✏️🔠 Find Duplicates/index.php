<?php
	// ‼ SUPER function
	// This plugin implements a SUPER function (it does not respect PDM conventions)
	// Detail: it will allow the user to modify files in the ARCHIVES folder
	// To Enable this plugin: Rename the plugin folder. (Folders starting with a '.' are ignored)


	$datadir='../ARCHIVES/';
	$client='client.html';
    //**************************************************************
	if(!isset($_GET['action'])){
		echo file_get_contents($client);
	}
	elseif($_GET['action']=='check'){
		echo json_encode(checkDuplicates($datadir));
	}
	elseif($_GET['action']=='file_delete'){
		//$_GET['filereference']
		if(unlink($datadir.$_GET['filereference'])){
			echo 'ok file deleted';
		}else{echo 'Error: could delete file';return false;}
	}
    elseif($_GET['action']=='file_get'){
        #mandatory: $_GET['sourceReference']
        $sourceReference=$_GET['sourceReference'];
        if(gettype($sourceReference==="string")){
            if($sourceReference !==''){
                if($sourceReference[0]!=='.'){
                    if(file_exists($datadir.$sourceReference)){
                        $headers = apache_request_headers();
                        // Checking if the client is validating his cache and if it is current.
                        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($datadir.$sourceReference))) {
                            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($datadir.$sourceReference)).' GMT', true, 304);
                        }
                        else{
                            header("Last-Modified: ".gmdate('D, d M Y H:i:s', filemtime($datadir.$sourceReference)).' GMT', true, 200);
                            header("Expires: ".gmdate('D, d M Y H:i:s \G\M\T',time()+86400)); // 1 day (archive never expire, let browser now they can cache data)
                            header("Pragma: cache");
                            header("Cache-Control: max-age=86400");
                            header("Content-Type:".mime_content_type($datadir.$sourceReference));
                            header("Content-Transfer-Encoding: Binary");
                            header("Content-Length:".filesize($datadir.$sourceReference));
                            $MSTimestamp=substr($sourceReference,0,strpos($sourceReference," "));
                            $STimestamp=floor(floatval($MSTimestamp)/1000);
                            $stringDate=date('Y-m-d H:i:s',$STimestamp).'.'.substr($MSTimestamp,-3);
                            $stringName=substr($sourceReference,15);
                            header('Content-Disposition:; filename="'.$stringDate.' '.$stringName.'"');
                            fpassthru(fopen($datadir.$sourceReference,'rb'));
                        }
                        
                    }else{echo 'Error server: source reference ('.$datadir.$sourceReference.') file does not exist';return false;}
                }else{echo 'Error server: source reference cannot be a system file';return false;}
            }else{echo 'Error server: source reference file cannot be empty';return false;}
        }else{echo 'Error server: source reference incorrect or not provided';return false;}
    }
	else{echo 'Server: Unknown action parameter';}


function checkDuplicates($datadir){
    chdir($datadir);
    $files_iterator = new DirectoryIterator('.');
    $files=array();
    foreach ($files_iterator as $file) {
        $filename=$file->getFilename();
        $filesize=my_filesize($filename);
        if($filesize!=0 ){//note: != filters out empty files and files above 2Gb (false)// do not replace with the exact comparison !==
            array_push($files,$filename);
        }
    }

	$result=[];
    $arr_sameSizeFiles=getSamesizesGroups($files);
    foreach ($arr_sameSizeFiles as $item) {
        $arr_sameCharFiles=getSame30charGroups($item['files']);
        foreach($arr_sameCharFiles as $item2) {
            $arr_sameHashesFiles=getSameHashesGroups($item['files']);
            $result=array_merge($result,$arr_sameHashesFiles);
        }
    }
    return $result;
}
function getSamesizesGroups($files){
	//returns an array of groups of files having the same size ['size'=>$filesize , 'files'=> [$file,$file,$file,$file,...]]
	$sizeArr = [];
	foreach ($files as $file) {$sizeArr[$file] = my_filesize($file);}
	$arr_uniqueSizes = array_unique($sizeArr);
	$arr_duplicateSizes = array_unique(array_diff_assoc($sizeArr, $arr_uniqueSizes));
	$arr_sameSizeFiles=[];
	foreach ($arr_duplicateSizes as $filesize) {array_push($arr_sameSizeFiles,array('size'=>$filesize,'files'=>array_keys($sizeArr, $filesize)));}
	return $arr_sameSizeFiles;
}
function getSame30charGroups($files){
	//returns an array of groups of files having the same first 10 characters middle and end ['char'=>$first20char , 'files'=> [$file,$file,$file,$file,...]]
	$arr = [];
	foreach ($files as $file) {
		$handle=fopen($file,'r');
		$signature=fread($handle,10);
		fseek ($handle,floor(my_filesize($file)/2));
		$signature= $signature.fread($handle,10);
		fseek ($handle,-10,SEEK_END);
		$signature= $signature.fread($handle,10);
		$arr[$file]=$signature;
		fclose($handle);
	}
	$arr_unique = array_unique($arr);
	$arr_duplicate = array_unique(array_diff_assoc($arr, $arr_unique));
	$arr_sameFiles=[];
	foreach ($arr_duplicate as $sig) {array_push($arr_sameFiles,array('char'=>$sig,'files'=>array_keys($arr, $sig)));}
	return $arr_sameFiles;
}
function getSameHashesGroups($files){
	//returns an array of groups of files having the same first 10 characters middle and end ['char'=>$first20char , 'files'=> [$file,$file,$file,$file,...]]
	$arr = [];
	foreach ($files as $file) {$arr[$file]=hash_file ('adler32',$file);}
	$arr_unique = array_unique($arr);
	$arr_duplicate = array_unique(array_diff_assoc($arr, $arr_unique));
	$arr_sameFiles=[];
	foreach ($arr_duplicate as $sig) {array_push($arr_sameFiles,array('hash'=>$sig,'files'=>array_keys($arr, $sig)));}
	return $arr_sameFiles;
}

function my_filesize($fp) {
	$filesize=@filesize($fp);//the @ suppress warning on this function (on some system, filesize for file above 2Gb returns false (32 bits limitation) => false=>2GB
	if($filesize===false){$filesize=intval(shell_exec ("stat -c %s '".$fp."'"));}
	return $filesize;
}

?>
