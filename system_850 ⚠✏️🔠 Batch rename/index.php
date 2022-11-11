<?php
	// ‼ SUPER function
	// This plugin implements a SUPER function (it does not respect PDM conventions)
	// Detail: it will allow the user to modify files in the ARCHIVES folder
	// To Enable this plugin: Rename the plugin folder. (Folders starting with a '.' are ignored)

    $ARCHIVESDIR='../ARCHIVES';
    $ARCHIVESDIR_cache='../ARCHIVES/.cache.json';
    $client="client.html";
    //********************************************

	if(!isset($_GET['action'])){
        echo file_get_contents($client);
	}
    elseif($_GET['action']=='simulate'){
        $filter_value= "/".base64_decode($_GET['filter_value'])."/" ;
        $replacement_value=base64_decode($_GET['replacement_value']);
        $filter_valid=filter_valid($filter_value);
        if($filter_valid){
            $files=test_rename_files($filter_value,$replacement_value,$ARCHIVESDIR);
        }
        else{
            $files=array();
        }
        echo json_encode(array(
            //'filter_value'=>$filter_value, this line makes it crash with 'é'
            'filter_valid'=>$filter_valid,
            'replacement_value'=>$replacement_value,
            'files'=>$files
        ));
	}
    elseif($_GET['action']=='save'){
        $filter_value= "/".base64_decode($_GET['filter_value'])."/" ;
        $replacement_value=base64_decode($_GET['replacement_value']);
        $filter_valid=filter_valid($filter_value);
        if($filter_valid){
            $files=scandir ($ARCHIVESDIR);
            $name_arr=array();
            $rename_arr=array();
            for($key=0;  $key < count($files); $key++){
                if(substr($files[$key],0,1)!=='.'){
                    $name=$files[$key];
                    $rename=preg_replace($filter_value,$replacement_value , $files[$key],1);
                    if($rename!==$name){
                        array_push($name_arr,$name);
                        array_push($rename_arr,$rename);
                    }
                }
            }
            if(!array_intersect($name_arr, $rename_arr)){
                //could be more efficient (if we renamed the file that conflicts before... but lets keep it simple, the burden on the user is limited and understandable)
                if(count(array_unique($rename_arr))===count($rename_arr)){
                    $error=false;
                    for($key=0;  $key < count($name_arr); $key++){
                        $name=$name_arr[$key];
                        $rename=$rename_arr[$key];
                        if(is_valid_reference($rename)===true){
                            if(file_exists($ARCHIVESDIR.'/'.$name)){
                                if(!file_exists($ARCHIVESDIR.'/'.$rename)){
                                    $res=rename($ARCHIVESDIR.'/'.$name,$ARCHIVESDIR.'/'.$rename);
                                    if($res){
                                        if(file_exists($ARCHIVESDIR_cache)){while(!unlink($ARCHIVESDIR_cache)){sleep(1);}clearstatcache();}
                                    }else{$error='NOK an error ';$key=count($name_arr);}
                                }else{$error='NOK file already exists ';$key=count($name_arr);}
                            }else{$error='NOK file does not exists ';$key=count($name_arr);}
                        }else{$error='NOK, Some files have illegal filenames: '.$rename.' > '.is_valid_reference($rename);$key=count($name_arr);}
                    }
                    if($error===false){
                        echo 'OK files renamed. Archives cache was deleted, You will probably need to reload all other pages';
                    }else{echo 'NOK: '.$error;}
                }else{echo 'NOK, Some files will be renamed with the same name';}
            }else{echo 'NOK, Some files to be renamed already exist, wont proceed';}
        }else{echo 'NOK, Filter is not valid';}
	}
    elseif($_GET['action']=='test'){
   echo preg_replace ( '/(.*)/','asdf' , 'hello',1);
    }
    else{
        echo 'Unknown action';
    }

function test_rename_files($filter_value,$replacement_value,$dir){
    $files=scandir ($dir);
    $results=array();
    $conflicts_token='';
    $renamed_array=array();
    for($key=0;  $key < count($files); $key++) {
        if(substr($files[$key],0,1)!='.'){
            $renamed=preg_replace ( $filter_value,$replacement_value , $files[$key],1);
            if($renamed!==$files[$key]){
                if(in_array($renamed,$files)){
                    $conflicts_token='exists';
                }elseif(in_array($renamed,$renamed_array)){
                    $conflicts_token='sameas';
                }elseif(is_valid_reference($renamed)!==true){
                    $conflicts_token='nvalid';
                }else{
                    $conflicts_token='';
                    array_push($renamed_array,$renamed);
                }
                array_push($results,array(
                    'name'=>$files[$key],
                    'match'=>'',
                    'rename'=>$renamed,
                    'conflict'=>$conflicts_token
                ));
            }
        }
    }
    return $results;
}

function filter_valid($filter_value){
    if(@preg_match($filter_value, null) === false){
        return false;
    }
    else{
        return true;
    }
}
function is_valid_reference($reference){
    // This function checks if the reference a valid reference as per the system requirement:
    // - A valid EXT3 filename
    //   - length <256
    //   - length >0
    //   - not '.'
    //   - not '..'
    //   - no '/' characters
    //   - no 'null' characters
    // - not start with a system prefix
    // - not format or control characters (i.e among 137,766 graphic Unicode characters) - not implemented yet
    if(strlen($reference)<256){
        if(strlen($reference)>0){
            if(substr($reference,0,1)!=='.'){
                if(strpos($reference,'/')===false){
                    //if(ctype_print($reference)===true){
                        return true;
                    //}else{return false;}
                }else{return 'Reference has an illegal \'\\\'';}
            }else{return 'Reference starts with a system-reserved prefix: \''.substr($reference,0,1).'\'';}
        }else{return 'Reference cannot but 0 length string';}
    }else{return 'Reference cannot exceed 255 characters (here: '.strlen($reference).')';}
}


?>
