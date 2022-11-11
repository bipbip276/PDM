<?php

  // ‼ SUPER function
  // This plugin implements a SUPER function (it does not respect PDM conventions)
  // Detail: it will allow the user to modify files in the ARCHIVES folder
  // To Enable this plugin: Rename the plugin folder. (Folders starting with a '.' are ignored)

  $datadir='../ARCHIVES/';
  $datadir_cache=$datadir.'.cache.json';
  $client='client.html';
  $readme='readme.txt';
  //**************************************************************
  if(!isset($_GET['action'])){
    echo file_get_contents($client);
  }
  elseif($_GET['action']=='getArchives'){
    if(!file_exists($datadir_cache)){//if no cache file
      $files_list=array();
      $files = new DirectoryIterator($datadir);
      foreach ($files as $file){
        if(!$file->isDot()){
          $filename=$file->getFilename();
          $filesize=@filesize($datadir.$filename);
          if($filesize===false){$filesize=intval (shell_exec ("stat -c %s '".$datadir.$filename."'"));}
          $files_list[] = array('reference'=>$filename,'size'=>$filesize);
        }
      }
      usort($files_list,function ($a, $b) {return (floatval($a['reference'].substr(0,strpos($a['reference'],' '))) > floatval($b['reference'].substr(0,strpos($b['reference'],' ')))) ? -1 : 1;});
      echo json_encode($files_list);
    }
    else{//use the cache file (much faster)
      echo file_get_contents($datadir_cache);
    }
  }
  elseif($_GET['action']=='file_rename'){
    //mandatory: $_GET['sourceReference']
    //mandatory: $_GET['destinationReference']
    $source=$datadir.$_GET['sourceReference'];
    $destination=$datadir.$_GET['destinationReference'];
    if(file_exists($datadir_cache)){while(!unlink($datadir_cache)){sleep(1);}clearstatcache();}
    if(file_exists($source)){
      if(!file_exists($destination)){
        if(rename($source,$destination)){
          echo 'ok file modified. Archives cache was deleted, You will probably need to reload all other pages';
        }else{echo 'Error: could rename file ';}
      }else{echo 'Error: destination file already exists.';}
    }else{echo 'Error: source file does not exist.';}
  }
  else{echo 'Server: Unknown action parameter';}

?>
