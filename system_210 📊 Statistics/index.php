<?php
    $ARCHIVES_directory   ='../ARCHIVES/';
    $ARCHIVES_cache       ='../ARCHIVES/.cache.json';
	$client_file='./client.html';
    //**************************************************************
    if(!isset($_GET['action'])){
        echo file_get_contents($client_file);
    }
    elseif($_GET['action']=='getArchives'){
        if(!file_exists($ARCHIVES_cache)){//if no cache file
            $files_list=array();
            $files = new DirectoryIterator($ARCHIVES_directory);
            foreach ($files as $file) {
                if(!$file->isDot()){
                    $filename=$file->getFilename();
                    $filesize=@filesize($ARCHIVES_directory.$filename);
                    if($filesize===false){$filesize=intval (shell_exec ("stat -c %s '".$ARCHIVES_directory.$filename."'"));}
                    $files_list[] = array('reference'=>$filename,'size'=>$filesize);
                }
            }
            usort($files_list,function ($a, $b) {return (floatval($a['reference'].substr(0,strpos($a['reference'],' '))) > floatval($b['reference'].substr(0,strpos($b['reference'],' ')))) ? -1 : 1;});
            echo json_encode($files_list);
        }
        else{//use the cache file (much faster)
            echo file_get_contents($ARCHIVES_cache);
        }
    }
    else{echo "error unknown action";}
?>
