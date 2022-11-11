<?php
    $namefile='../NAME.txt';
    $iconfile='../ICON.svg';
    $client='client.html';
	//***********************************
    if(!isset($_GET['action'])){
        echo file_get_contents($client);
    }
    elseif($_GET['action']=='getName'){
		echo file_get_contents($namefile);
	}
    elseif($_GET['action']=='getIcon'){
		echo file_get_contents($iconfile);
	}
    elseif($_GET['action']=='changeName'){
        $newname=$_GET['newName'];
        if(file_put_contents($namefile,$newname)!==false){
            echo 'OK, new name was written';
        }else{echo 'Could not write the new name';}
    }
    elseif($_GET['action']=='changeIcon'){
        if(move_uploaded_file($_FILES["newIcon"]["tmp_name"],'./tempFile.svg')){
            if( mime_content_type('./tempFile.svg')==='image/svg+xml' || mime_content_type('./tempFile.svg')==='image/svg' ){
                if(rename('./tempFile.svg',$iconfile)){
                    echo 'OK, new icon was saved';
                }else{echo 'Error while renaming the new file';}
            }else{echo 'Error the new file is not an SVG file';}
        }else{echo 'Error whil uploading the new file';}
    }
    else{echo 'Unknown action';}
?>
