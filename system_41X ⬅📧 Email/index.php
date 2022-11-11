<?php
    // This important plugin lets you import emails from an IMAP server
    //**************************************************************
    $client='email_client.html';
    $settingsTemplate='
    <?php
        // if $IMAPdata is an array with static IMAP credentials:
        // * This plugin will consider it is "admin-configured"
        // * this plugin will simply download the emails from IMAP and drop those in INBOX
        
        // if $IMAPdata is a string:
        // * This plugin will consider to allow user-configuration
        // * The user will be presented with an interface to put IMAP credentials
        //   and offered an option to save it in a json file in ARCHIVES
        //   ($adminSettingsthe) is the name of this json user-settings file that is archived) 
        //   (as per PDM conventions, no personal data is stored in the plugin)
        
        /*$IMAPdata=array(
            "IMAP_server_address"=>"server.com",     //address of the IMAP server
            "IMAP_server_port"=>"993",               // port of the IMAP server
            "IMAP_server_protocol"=>false,           //protocol of the IMAP server (false=plain IMAP, true=IMAP over SSL)
            "IMAP_server_login"=>"login",            //login of the IMAP server
            "IMAP_server_password"=>"password",      //password of the IMAP server
            "IMAP_server_pathFrom"=>"folder/path",   //path to the folder from which emails will be downloaded
            "IMAP_server_pathTo"=>""                 //path to the folder to which emails will be moved. An empty string ("") indicates that emails will be deleted on the server.
        );*/
        
        $IMAPdata="PDM Plugin - ⬅📧 Emails settings.json";
        
    ?>';
    
    //**************************************************************
	error_reporting(-1);
	ini_set('display_errors', 'On');
	date_default_timezone_set('Europe/Paris');

	if(file_exists('./settings.php')){
		include('./settings.php');
        if(gettype($IMAPdata)=="string"){
            if(!isset($_GET['action'])){
                echo file_get_contents($client);
            }
            elseif($_GET['action']=='getSettings'){
                echo retrieveSettingsFromArchives($IMAPdata,true);
            }
            elseif($_GET['action']=='checkSettings'){
                if(isset($_GET['settings'])){
                    if($_GET['settings']!=''){
                        echo json_encode(downloadEmails(json_decode($_GET['settings'],true)));
                    }else{echo "Error: no settings sent";}
                }else{echo "Error: no settings sent";}
            }
            elseif($_GET['action']=='saveSettings'){
                if(isset($_GET['settings'])){
                    if($_GET['settings']!=''){
						if(saveSettingsToArchives($IMAPdata,$_GET['settings'])===true){
							echo json_encode("OK Settings were saved\n\n(a backup of the settings was posted on your system)");
						}else{echo "Server Error: could not save settings";}
                    }else{echo "Server Error: settings is empty";}
                }else{echo "Server Error: settings is missing";}
            }
            elseif($_GET['action']=='downloadEmails'){
                if(isset($_GET['settings'])){
                    if($_GET['settings']!=''){
                        echo json_encode(downloadEmails(json_decode($_GET['settings'],true)));
                    }else{echo "Error: no settings sent";}
                }else{echo "Error: no settings sent";}
            }
            else{echo "Unrecognized action";}
        }
        elseif(gettype($IMAPdata)=="array"){
            $result=downloadEmails($IMAPdata);
            if($result["error"]===0){echo $result["output"];}
            else{
                echo '<h1>Error '.$result["error"].'</h1>';
                echo '(//1: failed to connect to server //2: pathFrom folder does not exist //3: pathTo folder does not exist)';
                echo '<hr><br>'.$result["output"];
            }
            
        }else{echo 'Error in the configuration file';}
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

    function updateArchivesCache($refreshCache=['del'=>null,'add'=>null]){
        if(is_dir($_SERVER['DOCUMENT_ROOT'].'/system/cache')){
            chdir($_SERVER['DOCUMENT_ROOT'].'/system/cache');
            if(file_exists('ARCHIVES.json')){
                $cache=json_decode(file_get_contents('ARCHIVES.json'),true);
                if($refreshCache['del']!==null){array_splice($cache['files'],array_search($refreshCache['del'],array_column($cache['files'],'reference')),1);}
                if($refreshCache['add']!==null){array_unshift($cache['files'],array('reference'=>$refreshCache['add'],'size'=>filesize($_SERVER['DOCUMENT_ROOT'].'/ARCHIVES/'.$refreshCache['add'])));}
                usort($cache['files'],function($a,$b) {return strnatcmp($b['reference'],$a['reference']);});
                $cache['timestamp']=round(microtime(true)*1000);
                file_put_contents('ARCHIVES.json',json_encode($cache));
                // now prepare the background process
                //launch a background cache refresh using bash/nohup
                //note: to avoid pushing unecessary updates, this script will not rechange the cache if no more files are found
                // in other word, archive edit (or create + delete) operations from another clients may go undetected by the client that triggers this function
                // once again, this is an acceptable SUPER functions 'bug'
                $generateScript_filename='temporary_cache_generating_script (email)_'.strval($cache['timestamp']).'.php';
                $generateScript='<?php'."\n";
                $generateScript.='  $cachedir=getcwd();'."\n";
                $generateScript.='  sleep(1);'."\n";//to make sure the timestamp from running the script will not be equal to $cache['timestamp']
                $generateScript.='  $cache=array();'."\n";
                $generateScript.='  $cache["timestamp"]=round(microtime(true)*1000);'."\n";
                $generateScript.='  $cache["files"]=array();'."\n";
                $generateScript.='  chdir("'.$_SERVER['DOCUMENT_ROOT'].'/ARCHIVES");'."\n";
                $generateScript.='  $filenames=glob("*",GLOB_NOSORT);'."\n";
                $generateScript.='  if(count($filenames)!='.strval(count($cache['files'])).'){'."\n";
                $generateScript.='    foreach($filenames as $filename){$cache["files"][] = array("reference"=>$filename,"size"=>filesize_($filename));};'."\n";
                $generateScript.='    usort($cache["files"],function($a,$b) {return strnatcmp($b["reference"],$a["reference"]);});'."\n";
                $generateScript.='    file_put_contents($cachedir."/ARCHIVES.json",json_encode($cache));'."\n";
                $generateScript.='  }'."\n";
                $generateScript.='  unlink("'.$generateScript_filename.'");'."\n";//self-clearing
                $generateScript.='  function filesize_($fp) {$filesize=@filesize($fp);if($filesize===false){$filesize=intval (shell_exec("stat -c %s \'".$fp."\'"));};return $filesize;}'."\n";
                $generateScript.='?>'."\n";
                file_put_contents($generateScript_filename,$generateScript);
                shell_exec("nohup php ./".$generateScript_filename." >/dev/null 2>&1 &");
                return true;
            }else{return true;}
        }else{return true;}
    }
    function downloadEmails($settings){
        $destination_dir=$_SERVER['DOCUMENT_ROOT'].'/DESKTOP';
		$server='';if($settings['IMAP_server_protocol']){$ssl='/ssl';}else{$ssl='';};$server='{'.$settings['IMAP_server_address'].':'.$settings['IMAP_server_port'].'/imap'.$ssl.'}';
        $login=$settings['IMAP_server_login'];
        $password=$settings['IMAP_server_password'];
        $output='';
        $error=0;//1: failed to connect to server //4: could not delete email //5: could not move email
        error_reporting(E_ERROR | E_PARSE);
        $mbox = imap_open($server.$settings['IMAP_server_pathFrom'],$login,$password);
        if($mbox ==false){
            $output.='Failed to connect to IMAP server:'."\n";
            $output.='- address: '.$settings['IMAP_server_address']."\n";
            $output.='- port: '.$settings['IMAP_server_port']."\n";
            $output.='- protocol: use SSL = '.$settings['IMAP_server_protocol']."\n";
            $output.='- login: '.$settings['IMAP_server_login']."\n";
            $output.='- password: XXXXXXXXXX'."\n";
            $error=1;
        }
        else{
            $output.='Successfully connected to IMAP server.'."\n";
            $mboxmsgno= imap_sort ($mbox,SORTDATE,0);
            $output.='Folder "'.$settings['IMAP_server_pathFrom'].'" has '.sizeof($mboxmsgno).' message(s)'."\n";
            $msgno_num=0;
            while($msgno_num<sizeof($mboxmsgno)){
                $msgno=$mboxmsgno[$msgno_num];
                //download message server info
                $headerinfo=imap_headerinfo($mbox,$msgno);
                //if(property_exists ($headerinfo,'message_id')){array_push($discard_msgid,$headerinfo->message_id);}
                //if(property_exists ($headerinfo,'date')){$date=strtotime ($headerinfo->date);}else{$date=time();}
                if(property_exists ($headerinfo,'from')){$from=$headerinfo->from[0]->mailbox.'@'.$headerinfo->from[0]->host;$from=' - from:'.$from;$from = str_replace("/", "-", $from);if(strlen($from)>50){$from=substr($from,0,47).'...';}}else{$from='';}
                if(property_exists ($headerinfo,'to')){$to=$headerinfo->to[0]->mailbox.'@'.$headerinfo->to[0]->host;$i=1;$iterations=min([5,sizeof($headerinfo->to)]);while($i!=$iterations){$to=$to.', '.$headerinfo->to[$i]->mailbox.'@'.$headerinfo->to[$i]->host;$i++;};$to=' - to:'.$to;$to = str_replace("/", "-", $to);if(strlen($to)>50){$to=substr($to,0,47).'...';}}else{$to='';}
                if(property_exists ($headerinfo,'subject')){$subject=$headerinfo->subject;$subject=mb_decode_mimeheader($subject);$subject=html_entity_decode($subject);$subject=str_replace('_',' ',$subject);$subject=trim($subject);$subject=preg_replace('/\s+/',' ',$subject);$subject=str_replace("/", "-", $subject);if(strlen($subject)>150){$subject=substr($subject,0,147).'...';}}else{$subject='(no subject)';}
                //mb_decode_mimeheader: get rid of imap encoding (=?<encoding code>?xOPT0NK7zPXOtLSmwO21xLrD09HH68fz?=)
                //html_entity_decode: //get rid of html encodings (&#29031&#29562...)
                //create filename
                $filename=$subject.$from;//150+50=200max
                $filename=strval(round(microtime(true)*1000)).' '.$filename.'.eml';//204
                $version=0;
                while(file_exists($destination_dir.'/'.$filename)){$version++;$filename=$subject.$from.' ('.$version.').eml';}//210 max...with a million identical files

                //download eml content (headers and body)
                $headers = imap_fetchheader($mbox, $msgno,FT_PREFETCHTEXT);
                $body = imap_body($mbox,$msgno);

                //save the file under filename
                $res=file_put_contents($destination_dir.'/'.$filename,$headers."\n".$body);
                if($res){
                    $output.="-> \"".$filename."\"\n";
                    if($settings['IMAP_server_pathTo']==''){
                        $res=imap_delete ($mbox,$msgno);
                        if(!$res){$output.="!! Error, could not delete this message on the server\n";$error=5;}
                    }
                    else{
                        $res=imap_mail_move ($mbox,$msgno,$settings['IMAP_server_pathTo']);
                        if(!$res){$output.="!! Error, could not move this message to folder \"".$settings['IMAP_server_pathTo']."\"\n";$error=6;}
                    }
                    $msgno_num++;
                }
                else{
                    $output.="!! Error, could not save email \"".$filename."\"\n";
                    $msgno_num=sizeof($mboxmsgno);
                }
            }
            if($settings['IMAP_server_pathTo']==''){
                $output.="\n";
                $output.="Downloaded messages were deleted on the server\n";
                $output.="Done!\n";
            }
            else{
                $output.="\n";
                $output.="Downloaded messages were moved to ".$settings['IMAP_server_pathTo']."\n";
                $output.="Done!\n";
            }
            imap_expunge($mbox);
            imap_close($mbox);
        }
        return array("error"=>$error, "output"=>$output);
    }
    function retrieveSettingsFromArchives($radicaString,$json=true){
        $backup=false;
        if(file_exists($_SERVER['DOCUMENT_ROOT'].'/system/cache/ARCHIVES.json')){
            chdir($_SERVER['DOCUMENT_ROOT'].'/system/cache');
            $cache=json_decode(file_get_contents('ARCHIVES.json'),true);
            $leng=strlen($radicaString)*-1;
            for($i = 0; $i <count($cache['files']); $i++){
                if(substr($cache['files'][$i]['reference'],$leng)==$radicaString){
                    $backup= file_get_contents($_SERVER['DOCUMENT_ROOT'].'/ARCHIVES/'.$cache['files'][$i]['reference']);
                    $i=count($cache['files']);
                }
            }
        }
        else{
            chdir($_SERVER['DOCUMENT_ROOT'].'/ARCHIVES/');
            $backedUpFiles=glob("*".$radicaString);
            if(count($backedUpFiles)!==0){
                $backup= file_get_contents(end($backedUpFiles));
            }
        }
        if($backup==false){
            $defaultSettings=array();
            $defaultSettings["IMAP_server_address"]="";//address of the IMAP server
            $defaultSettings["IMAP_server_port"]="";// port of the IMAP server
            $defaultSettings["IMAP_server_protocol"]=false;//protocol of the IMAP server (false=plain IMAP, true=IMAP over SSL)
            $defaultSettings["IMAP_server_login"]="";//login of the IMAP server
            $defaultSettings["IMAP_server_password"]="";//password of the IMAP server
            $defaultSettings["IMAP_server_pathFrom"]="";//path to the folder from which emails will be downloaded
            $defaultSettings["IMAP_server_pathTo"]="";//path to the folder to which emails will be moved. An empty string ('') indicates that emails will be deleted on the server.
            if($json){return json_encode($defaultSettings);}
            else{return $defaultSettings;}
        }else{
            if($json){return $backup;}
            else{return json_decode($defaultSettings,true);}
        }
        
        
    }
	function saveSettingsToArchives($radicaString,$settings){
		$res=file_put_contents($_SERVER['DOCUMENT_ROOT'].'/ARCHIVES/'.strval(round(microtime(true)*1000)).' '.$radicaString,$settings);
		if($res!==false){
			if(updateArchivesCache($refreshCache=['del'=>null,'add'=>$settingsBackupFile])===true){
				return true;
			}else{return false;}
		}else{return false;}
	}
    function checkSettings($settings){
        $server='';if($settings['IMAP_server_protocol']){$ssl='/ssl';}else{$ssl='';};$server='{'.$settings['IMAP_server_address'].':'.$settings['IMAP_server_port'].'/imap'.$ssl.'}';
        $login=$settings['IMAP_server_login'];
        $password=$settings['IMAP_server_password'];
        $output='';
        $error=0;//1: failed to connect to server //2: pathFrom folder does not exist //3: pathTo folder does not exist
        error_reporting(E_ERROR | E_PARSE);
        //################
        //Test IMAP server
        $mbox = imap_open($server,$login,$password);
        if($mbox ==false){
            $output.='Failed to connect to IMAP server:'."\n";
            $output.='- address: '.$settings['IMAP_server_address']."\n";
            $output.='- port: '.$settings['IMAP_server_port']."\n";
            $output.='- protocol: use SSL = '.$settings['IMAP_server_protocol']."\n";
            $output.='- login: '.$settings['IMAP_server_login']."\n";
            $output.='- password: '.$settings['IMAP_server_password']."\n";
            $error=1;
        }
        else{
            $output.="Successfully connected to IMAP server. Folders are:\n";
            $foldersRAW = imap_list($mbox, $server,"*");
            $folders=array();
            foreach ($foldersRAW as $folderRAW){
                //remove the leading '{server}' data
                $folder=trim(explode('}',$folderRAW)[1]);
                array_push($folders,$folder);
                $output.="- \"".$folder."\"\n";
            }
            $output.="\n";
            imap_close($mbox);
            //#########################
            //Test IMAP server pathFrom
            if(in_array($settings['IMAP_server_pathFrom'],$folders)){
                $mbox = imap_open($server.$settings['IMAP_server_pathFrom'],$login,$password);
                if($mbox !==false){
                    $output.='Emails will be downloaded from folder "'.$settings['IMAP_server_pathFrom']."\"\" (contains ".sizeof(imap_sort ($mbox,SORTDATE,0))." messages)"."\n";
                }else{$output.='Failed to connect to IMAP server "'.$settings['IMAP_server_pathFrom']."\"\n";$error=1;}
            }else{$output.="Configuration Error; Folder \"".$settings['IMAP_server_pathFrom']."\" does not exist\n";$error=2;}
            $output.="\n";
            imap_close($mbox);
            //#######################
            //Test IMAP server pathTo
            if($settings['IMAP_server_pathTo']!=''){
                if(in_array($settings['IMAP_server_pathTo'],$folders)){
                    $mbox = imap_open($server.$settings['IMAP_server_pathTo'],$login,$password);
                    if($mbox !==false){
                        $output.='Emails will then be moved to folder "'.$settings['IMAP_server_pathTo']."\"\" (contains ".sizeof(imap_sort ($mbox,SORTDATE,0))." messages)"."\n";
                    }else{$output.='Failed to connect to IMAP server "'.$settings['IMAP_server_pathTo']."\"\n";$error=1;}
                }else{$output.="Configuration Error; Folder \"".$settings['IMAP_server_pathTo']."\" does not exist\n";}
                imap_close($mbox);
            }else{$output.='Then, EMAILS WILL BE DELETED from server as per the settings';}
        }
        return array("error"=>$error, "output"=>$output);
    }

?>
