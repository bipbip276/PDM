<?php

	// REQUIRES SERVER-LEVEL CONFIGURATION
	// you need to taylor it to some point to work
	// 
	// $recyclingDir: which folder contains deleted files:
	$recyclingDir=null;

    //**************************************************************
    $client='recycling_client.html';
	if(is_dir($recyclingDir)){
		if(!isset($_GET['action'])){
			echo file_get_contents($client);
		}
		elseif($_GET['action']=='getRecycling'){
			// Use the server plugin API ?action=SERVER_getRecycling
			// returns an array of files in the recycling (sorted by deletion time - oldest first):
			// ['reference', 'size','time'(=time of deletion)]
			// ['reference', 'size','time'(=time of deletion)]
			// ['reference', 'size','time'(=time of deletion)]
			// ['reference', 'size','time'(=time of deletion)]
			if ($dh = opendir($recyclingDir)) {
				$files_list=array();
				$dir_size=0;
				while (($file = readdir($dh)) !== false){
					if($file[0]!=='.'){
						$files_list[] = array('reference'=>$file,'size'=>filesize($recyclingDir.$file),'time'=>filemtime($recyclingDir.$file));
					}//else{skip}
				}//finished
				closedir($dh);
				usort($files_list, function($a, $b) {return $a['time'] - $b['time'];});
				echo json_encode($files_list);
			}else {echo "error while opening inbox folder ";}
		}
		elseif($_GET['action']=='deleteFile'){
			//$_GET['filereference']
			if(unlink($recyclingDir.$_GET['filereference'])){
				echo 'ok, file deleted';
			}else{echo 'Error: could delete file';}
		}
		elseif($_GET['action']=='deleteALL'){
			$files = glob($recyclingDir.'*');
			foreach($files as $file){
				if(is_file($file))
				unlink($file);
			}
			echo 'OK, the files were deleted on the server';
		}
		else{
			echo 'Unknown action';
		}
	}
	else{
		echo "This system has no recycling folder or is not properly configured. Ask your admin."
	}

?>
