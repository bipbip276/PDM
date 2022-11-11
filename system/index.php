<?php

    //Where to find USER PERSONAL DATA:
    //********************************
    $settings['rootDir']  ='../';
    $settings['archives'] ='../ARCHIVES/'; // ARCHIVES data folder
    $settings['desktop']  ='../DESKTOP/';  // DESKTOP data folder
    $settings['icon']     ='../ICON.svg';  // ICON file
    $settings['name']     ='../NAME.txt';  // NAME file
    $settings['plugins_pattern'] ='/system_[0-9][0-9][0-9]\ (.*)\.php/';  //plugins' pattern in rootDir (should capture the cometic name)


    // *******************************************************************************
    // TECHNICAL SETTINGS ************************************************************
    // *******************************************************************************
 
    // ABOUT POLLING
    // -------------
    // The server-script offers normal poll (?action=loadUpdate) and long poll (?action=loadUpdateLongpoll to the client-page
    // The client-page can be configured to do long-polling, Time-fixed polling or no to do polling
    // * Long polling reacts faster to changes on the server, and execute plugins-daemons with a higher frequency but require a multithreaded server (not php dead-simple built-in server)
    // * Time-fixed polling is more reliable and run fine on single thread servers (such as PHP built-in http server). Change are detected and plugins-daemons are executed only once at each polling.
    // * No poll: the client is not notified of server updates unless it does a server modication, in which case it will recieve the latest update.

    //loadUpdateLongpoll_timeout
    // IF THE CLIENT IS SET TO USE LONGPOLL: Maximum time, in seconds, the server will hold the response on a long-poll request
    // 50 seconds works fine on OVH hosting service. (I guess= 60 seconds - latency).
    // If too short, server and network load will be higher than needed
    // If too long the http server, close the connection with a 504 Gateway Timeout (handled by the client but unnice).
    $settings['loadUpdateLongpoll_timeout'] =50;
    
    //loadUpdateLongpoll_diskPollFreq
    // IF THE CLIENT IS SET TO USE LONGPOLL: time, in seconds, between 2 local disk polling (to detect changes) and execute plugins-daemons, while a long polls is pending
    // 3 seconds seems reasonable (average dielay between change and user-notification will be 3 seconds + network latency)
    // If too short, server disk load higher than necessary for a marginal gain in user-experience
    // If too long, user is waiting longer
    // There is an opportunity in the future to get rid of this setting with linux or php 'inotify', but those are not accessible on my hosting service.
    $settings['loadUpdateLongpoll_diskPollFreq'] =3;


    // ABOUT CACHE
    // -----------
    // This script uses cache to access user DATA (in DESKTOP, ARCHIVES)
    // This is because
    // * For KISS, ROBUSTNESS and DATA ACCESSIBILITY reasons, I do not want to use database software and keep a plain 'files in folders' layout
    // * Except in specific configurations folder listing gets slow (like >5seconds) with more than a few thousand files (but it is reasonnable and I expect computer performance to increase faster than the number of files)
    // * without cache, we rely on USER DATA folders' timestamp, which may not be reliable due to FS-level cache setting on the hosting provinder platform
    // However, there is a great opportunity to simplify the code by removing cache
    // This is theoretically possible if the underlying FileSystem is ReiserFS 4 (to be confirmed) and/or depending on FS-level cache, kernel-level cache settings, etc...
    // To do this modification, check for the keyword 'nocache'
	// All settings / rules for cache are hard coded in 3 functions:
    // * function get_archives
    $settings['archivesCache']=$settings['archives'].'.cache.json';

    // filemtime limitation #fmtLIM
    // ----------------------------
    // PHP only supports filemtime to the seconds because it is the highest common denominator between OS/filesystems
    // Since we use timestamp to detect an update of the data folders (DESKTOP and ARCHIVES) this either:
    // * limits the maximum modification rate at 1/second
    // * or forces using a "filemtimeMS" function that calls an external function to get the filemtime in ms. This adds an assumption on the OS and filesystem.
    // For the sake of robustness and lazyness, and considering the usage of this script, I decided to limit the rate of update to 1/second
    // Check the keyword #fmtLIM to find code that could be removed if PHP eventually supports miliseconds filemtime in the future
    
    

	// ABOUT PLUGINS
    // -----------
	// 
    
    //********************************************************************************
    // Server Developpment settings / No need to touch
    ini_set('display_errors','On');            // to be clean, can be commented
    //date_default_timezone_set('Europe/Paris'); // not important, just to avoid warnings if no default set on your system, all timezone is control on the client side. This script only manipulates timestamps (in ms to garanty a unic ID, not unix in seconds)
    //********************************************************************************

    if(!isset($_GET['action'])){
        echo file_get_contents('client.html');
    }
    elseif($_GET['action']=='load1'){
        // This is the first packet sent, for the client to immediately show content
        $packet= array();
        $packet['DESKTOP']=get_desktop(true);
        $packet['ARCHIVES_LST']=get_archives(false);
        $packet['SETTINGS']=get_clientSettings();
        $packet['PLUGINS']=get_plugins();
        $packet['status']='ok, firstPacket';
        echo json_encode($packet);
    }
    elseif($_GET['action']=='load2'){
        // This is the first packet sent, for the client to have all ARCHIVES content
        $packet=array();
        $packet['ARCHIVES']=get_archives(true);
        $packet['status']='ok, all archives.';
        echo json_encode($packet);
    }
    elseif($_GET['action']=='loadUpdate'){
        $packet=array();
        $packet['DESKTOP']=get_desktop(true);
        $packet['ARCHIVES_LST']=get_archives(false);
        echo json_encode($packet);
    }
    elseif($_GET['action']=='loadUpdateLongpoll'){
        // Mandatory: $_GET['timestamp_DESKTOP'];
        // Mandatory: $_GET['timestamp_ARCHIVES'];
        $until=get_timestamp()+($settings['loadUpdateLongpoll_timeout']*1000);
        // The following time delay is usefull
        // Not to get kicked out during the developpment phase
        // when ?action=clear, to avoid rapid error popups before the page can even refresh
		sleep($settings['loadUpdateLongpoll_diskPollFreq']);
        while(true){
            ##nocache: if cache is removed changes should be detected by comparing the client-sent timestamps to the USER DATA folder's timestamp (filemtime)
            if(get_desktop(null) !=$_GET['timestamp_DESKTOP']||get_archives(null) !=$_GET['timestamp_ARCHIVES']){
                $packet=array();
				$packet['DESKTOP']=get_desktop(true);
                $packet['ARCHIVES_LST']=get_archives(false);
				echo json_encode($packet);
                exit(0);
            }
			elseif(get_timestamp()>=$until){//timeup
                $packet=array();
                echo json_encode($packet);
				exit(0);
			}
            else{//loop
				sleep($settings['loadUpdateLongpoll_diskPollFreq']);
			}
        }
    }
    elseif($_GET['action']=='edit'){
        #optional: $_GET['sourceReference']
        #  missing:create (with or without data)
        #  valid desktop source reference
        #optional: $_GET['destinationReference']
        #  missing:delete
        #  valid desktop destination reference
        #optional: destinationData
        #  missing: preserve data or create empty file
        #  $_GET['method']: 'textData' or 'fileData'
        #  $_POST['textData'] OR $_FILES['fileData']
        #
        # NOTE: if both sourceReference and destinationReference are missing: logical problem
        # NOTE: if both sourceReference and destinationReference are equal: only the data will be modified
        # NOTE: if both sourceReference and destinationReference are equal: and destination is missing: logical problem
        if(time()===get_desktop(null)){sleep(1);}#fmtLIM
        if(!isset($_GET['sourceReference'])){
            if(!isset($_GET['destinationReference'])){
				// CASE 1: create and delete
                echo "Warning: no operation on file: there probably is a client-side logics problem here.";
            }
            else{
                $destinationReference=$_GET['destinationReference'];
				if(!isset($_GET['method'])){
					// CASE 2: create without data
					if(isValidDesktopDestinationReference($destinationReference)){
						if(touch($settings['desktop'].$destinationReference)){
							$packet=array();
							$packet['DESKTOP']=get_desktop(true);
							echo json_encode($packet);
						}else{echo 'Error while creating the new file';}
					}else{echo 'Error: destinationReference is not valid4';}
				}
				else{
					$destinationDataMethod=$_GET['method'];
					if($destinationDataMethod=='textData'){$destinationDataContent=$_POST['textData'];}elseif($destinationDataMethod=='fileData'){$destinationDataContent=$_FILES['fileData'];}else{echo 'Error: invalid data format';}
					// CASE 3: create with data
					if(isValidDesktopDestinationReference($destinationReference)){
						if(isValidDestinationData($destinationDataMethod,$destinationDataContent)){
							if($_GET['method']=='textData'){
								if(file_put_contents($settings['desktop'].$destinationReference,$_POST['textData'],LOCK_EX)!==false && touch($settings['desktop'])){
									$packet=array();
									$packet['DESKTOP']=get_desktop(true);
									echo json_encode($packet);
								}else{echo 'Error while puting data into the file';}
							}
							elseif($_GET['method']=='fileData'){
								if(move_uploaded_file($_FILES['fileData']["tmp_name"],$settings['desktop'].$destinationReference)){
									$packet=array();
									$packet['DESKTOP']=get_desktop(true);
									echo json_encode($packet);
								}else{echo 'Error while moving in the new file';}
							}else{echo 'Error, destination data method unknown';}
						}else{echo 'Error: destinationData is not valid';}
					}else{echo 'Error: destinationReference is not valid3';}
				}
            }
        }
        else{
            $sourceReference=$_GET['sourceReference'];
            if(!isset($_GET['destinationReference'])){
				// CASE 3: delete
                if(isValidDesktopSourceReference($sourceReference)){
                    if(unlink($settings['desktop'].$sourceReference)){
                        $packet=array();
                        $packet['DESKTOP']=get_desktop(true);
                        echo json_encode($packet);
                    }else{echo 'Error while deleting the file';}
                }else{echo 'Error: sourceReference is not valid';}
            }
            else{
                $destinationReference=$_GET['destinationReference'];
				if(!isset($_GET['method'])){
					// CASE 4: rename without data
					if(isValidDesktopSourceReference($sourceReference)){
						if(isValidDesktopDestinationReference($destinationReference)){
                            if($sourceReference!==$destinationReference){
                                if(rename($settings['desktop'].$sourceReference,$settings['desktop'].$destinationReference)){
                                    $packet=array();
                                    $packet['DESKTOP']=get_desktop(true);
                                    echo json_encode($packet);
                                }else{echo 'Error: could not rename the file';}
                            }else{echo 'Error: sourceReference and destinationReference are identical while there is no destination data';}
						}else{echo 'Error: destinationReference is not valid5';}
					}else{echo 'Error: sourceReference is not valid2';}
				}
				else{
					$destinationDataMethod=$_GET['method'];
					if($destinationDataMethod=='textData'){$destinationDataContent=$_POST['textData'];}elseif($destinationDataMethod=='fileData'){$destinationDataContent=$_FILES['fileData'];}else{echo 'Error: invalid data format';}
					// CASE 5: rename or not with data update
					if(isValidDesktopSourceReference($sourceReference)){
						if($destinationReference==$sourceReference){$res=true;}elseif(isValidDesktopDestinationReference($destinationReference)){$res=true;}else{$res=false;}
                        if($res){
							if(isValidDestinationData($destinationDataMethod,$destinationDataContent)){
								if($destinationReference!==$sourceReference){$res=rename($settings['desktop'].$sourceReference,$settings['desktop'].$destinationReference);}else{$res=true;}
                                if($res){
									if($_GET['method']=='textData'){
										if(file_put_contents($settings['desktop'].$destinationReference,$_POST['textData'],LOCK_EX)!==false && touch($settings['desktop'])){
											$packet=array();
											$packet['DESKTOP']=get_desktop(true);
											echo json_encode($packet);
										}else{echo 'Error while puting data into the file';}
									}
									elseif($_GET['method']=='fileData'){
										if(move_uploaded_file($_FILES['fileData']["tmp_name"],$settings['desktop'].$destinationReference)){
											$packet=array();
											$packet['DESKTOP']=get_desktop(true);
											echo json_encode($packet);
										}else{echo 'Error while moving in the new file';}
									}else{echo 'Error, destination data method unknown';}
								}else{echo 'Error: could not rename the file';}
							}else{echo 'Error: destinationData is not valid';}
						}else{echo 'Error: destinationReference is not valid'. $destinationReference.$sourceReference;}
					}else{echo 'Error: sourceReference is not valid';}
				}
            }
        }
    }
    elseif($_GET['action']=='register'){
        #mandatory: $_GET['sourceReference']
        #  valid desktop source reference
        #optional: $_GET['destinationName']
        #  missing: keep the same name
        #  valid archies destination name
        if(time()===get_desktop(null) || time()===get_archives(null)){sleep(1);}#fmtLIM
		$sourceReference=$_GET['sourceReference'];
        if(isset($_GET['destinationName'])){$destinationReference=get_timestamp(true).' '.$_GET['destinationName'];}
        else{$destinationReference=get_timestamp(true).' '.substr($_GET['sourceReference'],strpos($_GET['sourceReference'],' ')+1);}
        if(isValidDesktopSourceReference($sourceReference)){
            if(isValidArchivesDestinationReference($destinationReference)){
                if(rename($settings['desktop'].$sourceReference,$settings['archives'].$destinationReference)){
                    $packet=array();
                    $packet['DESKTOP']=get_desktop(true);
                    $packet['ARCHIVES_LST']=get_archives($destinationReference);
                    echo json_encode($packet);
                }else{echo 'Error: could notre rename file';}
            }else{echo 'Error: destinationReference is not valid';}
        }else{echo 'Error: sourceReference is not valid';}
    }
    elseif($_GET['action']=='copyback'){
        #mandatory: $_GET['sourceReference']
        #  valid archives source reference
        #optional: $_GET['destinationReference']
		#  missing: create a reference
        #  valid desktop destination reference
        
        if(time()===get_desktop(null)){sleep(1);}#fmtLIM
        $sourceReference=$_GET['sourceReference'];
		if(isset($_GET['destinationReference'])){$destinationReference=$_GET['destinationReference'];}
        else{$destinationReference=get_timestamp(true).'*'.substr($_GET['sourceReference'],strpos($_GET['sourceReference'],' ')+1);}
        if(isValidArchivesSourceReference($sourceReference)){
            if(isValidDesktopDestinationReference($destinationReference)){
                if(copy($settings['archives'].$sourceReference,$settings['desktop'].$destinationReference)){
                    $packet=array();
                    $packet['DESKTOP']=get_desktop(true);
                    echo json_encode($packet);
                }else{echo 'Error: could notre copy file';}
            }else{echo 'Error: destinationReference is not valid';}
        }else{echo 'Error: sourceReference is not valid';}
    }
    elseif($_GET['action']=='desktop_get'){
        #mandatory: $_GET['sourceReference']
        serveDesktopFile($_GET['sourceReference']);
    }
    elseif($_GET['action']=='archives_get'){
        #mandatory: $_GET['sourceReference']
        serveArchivesFile($_GET['sourceReference']);
    }
	else{echo "error unknown action: ".$_GET['action'];}

    // LIBRARIES
    // Specific User DATA files manipulations - top level
    function serveDesktopFile($sourceReference){
		global $settings;
        if(isValidDesktopSourceReference($sourceReference)){
            $ts = gmdate("D, d M Y H:i:s") . " GMT";
            header("Expires: $ts");
            header("Last-Modified: $ts");
            header("Pragma: no-cache");
            header("Cache-Control: no-cache, must-revalidate");
            header("Content-Type:".mime_content_type($settings['desktop'].$sourceReference));
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length: ".filesize($settings['desktop'].$sourceReference));
            //strandard headers:
            //header("Content-Disposition: attachment; filename*=utf-8' '".rawurlencode($filename));
            //header('Content-Disposition: attachment; filename="'.$filename.'"');
            //header('Content-Disposition: inline; filename="'.$filename.'"');
            // the following header is illegal but it allows me to force download with a name (not 'server.php') even for PDF on mobiles
            // while not specifying a content dispostion (let the browser decide)
            //header('Content-Disposition:; filename="'.basename($sourcePath).'"');
            header('Content-Disposition:; filename="'.substr($sourceReference,15).'"');
            fpassthru(fopen($settings['desktop'].$sourceReference,'rb'));
            // simple version
            //header('Location: '.'ARCHIVES'.$sourceReference);
        }else{echo 'Error, sourcePath is not valid';return false;}
    }
    function serveArchivesFile($sourceReference){
		global $settings;
        if(isValidArchivesSourceReference($sourceReference)){
             $headers = apache_request_headers();
            // Checking if the client is validating his cache and if it is current.
            if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($settings['archives'].$sourceReference))) {
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($settings['archives'].$sourceReference)).' GMT', true, 304);
            }
            else{
                header("Last-Modified: ".gmdate('D, d M Y H:i:s', filemtime($settings['archives'].$sourceReference)).' GMT', true, 200);
                header("Expires: ".gmdate('D, d M Y H:i:s \G\M\T',time()+86400)); // 1 day (archive never expire, let browser now they can cache data)
                header("Pragma: cache");
                header("Cache-Control: max-age=86400");
                header("Content-Type:".mime_content_type($settings['archives'].$sourceReference));
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length:".filesize($settings['archives'].$sourceReference));
                $MSTimestamp=substr($sourceReference,0,strpos($sourceReference," "));
                $STimestamp=floor(floatval($MSTimestamp)/1000);
                $stringDate=date('Y-m-d H:i:s',$STimestamp).'.'.substr($MSTimestamp,-3);
                $stringName=substr($sourceReference,15);
                header('Content-Disposition:; filename="'.$stringDate.' '.$stringName.'"');
                fpassthru(fopen($settings['archives'].$sourceReference,'rb'));
            }
        }else{echo 'Error, sourcePath is not valid';return false;}
    }
    function isValidDesktopSourceReference($sourceReference){
		global $settings;
        if(gettype($sourceReference==="string")){
            if($sourceReference !==''){
                if($sourceReference[0]!=='.'){
                    if(file_exists($settings['desktop'].$sourceReference)){
                        return true;
                    }else{echo 'Error server: source reference ('.$settings['desktop'].$sourceReference.') file does not exist';return false;}
                }else{echo 'Error server: source reference cannot be a system file';return false;}
            }else{echo 'Error server: source reference file cannot be empty';return false;}
        }else{echo 'Error server: source reference incorrect or not provided';return false;}
    }
    function isValidDesktopDestinationReference($destinationReference){
		global $settings;
        if(gettype($destinationReference==="string")){
            if(check_utf8($destinationReference)){
                if(preg_match ('/^\d{14}[\*\:][^\/]{1,240}$/',$destinationReference)===1){
                    if(!file_exists($settings['desktop'].$destinationReference)){
                        return true;
                    }else{echo 'Error server: a file with the same name already exists';return false;}
                }else{echo 'Error server: desttinationReference is not correct (max length: 255, max timestamp 99999999999999, no "/" character): '.$destinationReference;return false;}
            }else{echo 'Error server: desttinationReference is not a well-formed UTF8 string';return false;}
        }else{echo 'Error server: destination reference incorrect or not provided';return false;}
    }
    function isValidArchivesSourceReference($sourceReference){
		global $settings;
        if(gettype($sourceReference==="string")){
            if($sourceReference !==''){
                if($sourceReference[0]!=='.'){
                    if(file_exists($settings['archives'].$sourceReference)){
                        return true;
                    }else{echo 'Error server: source reference ('.$settings['archives'].$sourceReference.') file does not exist';return false;}
                }else{echo 'Error server: source reference cannot be a system file';return false;}
            }else{echo 'Error server: source reference file cannot be empty';return false;}
        }else{echo 'Error server: source reference incorrect or not provided';return false;}
    }
    function isValidArchivesDestinationReference($destinationReference){
		global $settings;
        if(check_utf8($destinationReference)){
			if(preg_match ('/^\d{14}\ [^\/]{1,240}$/',$destinationReference)===1){
				if(!file_exists($settings['archives'].$destinationReference)){
					return true;
				}else{echo 'Error server: a file with the same name already exists';return false;}
			}else{echo 'Error server: desttinationReference is not correct (max length: 255, max timestamp 99999999999999, no "/" character): '.$destinationReference;return false;}
		}else{echo 'Error server: desttinationReference is not a well-formed UTF8 string';return false;}
    }
    function isValidDestinationData($destinationDataMethod,$destinationDataContent){
        if($destinationDataMethod=='textData'){
			if(is_string($destinationDataContent)){
				return true;
			}
			else{echo 'Error server: destinationData has not text data';return false;}
        }
		elseif($destinationDataMethod=='fileData'){
			if($destinationDataContent['error']==UPLOAD_ERR_OK){
				return true;
			}
			else{echo 'Error server: destinationData does not point to a valid posted file';return false;}
		}else{echo 'Error server: destinationData has no recognised method';return false;}
    }
    // Other specific functions
    function get_desktop($mode){
		// $mode:
        //   null => returns only the timestamp
        //   true => returns the timestamp and the files
        global $settings;
        if(!is_dir($settings['desktop'])){die("Error: DESKTOP data folder was not found on the server.");}
        if($mode===null){
            return filemtime(__DIR__.'/'.$settings['desktop']);
        }
        else{
            $timestamp=filemtime(__DIR__.'/'.$settings['desktop']);
            chdir(__DIR__.'/'.$settings['desktop']);
            $files_list=array();
            foreach(glob('*') as $filename){
                if(preg_match ('/^\d\d\d\d\d\d\d\d\d\d\d\d\d\d[\*\:].*$/',$filename)!==1){
                    $standardizedFilename=$filename;
                    if(strlen($standardizedFilename)>240){$ext= pathinfo($standardizedFilename,PATHINFO_EXTENSION);$standardizedFilename=substr($standardizedFilename,0,(236-strlen($ext))).'(…).'.$ext;}
                    $standardizedFilename=get_timestamp(true).'*'.$standardizedFilename;
                    rename($filename,$standardizedFilename);
                    $filename=$standardizedFilename;
                }
                array_unshift($files_list,array('reference'=>$filename,'size'=>filesize($filename)));
            }
            return array('files' =>$files_list,'timestamp' => $timestamp);
        }
    }
    function get_archives($mode){
        global $settings;
        if(!is_dir($settings['archives'])){die("Error: ARCHIVES data folder was not found on the server.");}
		// $mode:
        //   true => returns all the archives
        //   false => returns the 100 latest
        //   null => returns only the timestamp
        //   string => returns a fake '1 lastest' file and launch a cache refresh in the background (must be called AFTER the files were copied in the folder)
		//          WARNING: for the client, when receiving a 'last 100 packkets' some new files might be older that files it already took into account
        if($mode===null){
            return filemtime(__DIR__.'/'.$settings['archivesCache']);
        }
        elseif($mode===true){
            $cache=array();
            if(!file_exists($settings['archivesCache'])){
                $cache['files']=array();
                chdir(__DIR__.'/'.$settings['archives']);
                foreach(glob('*') as $filename){$cache['files'][] = array('reference'=>$filename,'size'=>filesize($filename));}//sorted alphabetically
                chdir(__DIR__);
                //fine sorting: segragate files starting with '-' from those starting with '0' and sort accordingly
                $i=0;while($cache['files'][$i]['reference'][0]=='-'){$i++;}if($i!=0){$cache['files']=array_merge(array_reverse(array_slice($cache['files'],$i+1,count($cache['files']))),array_slice($cache['files'],0,$i));}
                file_put_contents($settings['archivesCache'],json_encode($cache['files']),LOCK_EX);
                $cache['timestamp']=filemtime(__DIR__.'/'.$settings['archivesCache']);
            }
            else{
                $cache['timestamp']=filemtime(__DIR__.'/'.$settings['archivesCache']);
                $cache['files']=json_decode(file_get_contents($settings['archivesCache']),true);
            }
            return $cache;
        }
        elseif($mode===false){
            $cache=array();
            if(!file_exists($settings['archivesCache'])){
                $cache['files']=array();
                chdir(__DIR__.'/'.$settings['archives']);
                foreach(glob('*') as $filename){$cache['files'][] = array('reference'=>$filename,'size'=>filesize($filename));}//sorted alphabetically
                chdir(__DIR__);
                //fine sorting: segragate files starting with '-' from those starting with '0' and sort accordingly
                $i=0;while($cache['files'][$i]['reference'][0]=='-'){$i++;}if($i!=0){$cache['files']=array_merge(array_reverse(array_slice($cache['files'],$i+1,count($cache['files']))),array_slice($cache['files'],0,$i));}
                file_put_contents($settings['archivesCache'],json_encode($cache['files']),LOCK_EX);
                $cache['timestamp']=filemtime(__DIR__.'/'.$settings['archivesCache']);
            }
            else{
                $cache['timestamp']=filemtime(__DIR__.'/'.$settings['archivesCache']);
                $cache['files']=json_decode(file_get_contents($settings['archivesCache']),true);
            }
            $cache['files']=array_slice($cache['files'],0,100);
            return $cache;
        }
        else{
            $cache=array();
            if(!file_exists($settings['archivesCache'])){
                $cache['files']=array();
                chdir(__DIR__.'/'.$settings['archives']);
                foreach(glob('*') as $filename){$cache['files'][] = array('reference'=>$filename,'size'=>filesize($filename));}//sorted alphabetically
                chdir(__DIR__);
                //fine sorting: segragate files starting with '-' from those starting with '0' and sort accordingly
                $i=0;while($cache['files'][$i]['reference'][0]=='-'){$i++;}if($i!=0){$cache['files']=array_merge(array_reverse(array_slice($cache['files'],$i+1,count($cache['files']))),array_slice($cache['files'],0,$i));}
                file_put_contents($settings['archivesCache'],json_encode($cache['files']),LOCK_EX);
                $cache['timestamp']=filemtime(__DIR__.'/'.$settings['archivesCache']);
            }
            else{
                $cache['files']=json_decode(file_get_contents($settings['archivesCache']),true);
                array_unshift($cache['files'],array('reference'=>$mode,'size'=>filesize($settings['archives'].$mode)));
                file_put_contents($settings['archivesCache'],json_encode($cache['files']),LOCK_EX);
                $cache['timestamp']=filemtime(__DIR__.'/'.$settings['archivesCache']);
            }
            $cache['files']=array_slice($cache['files'],0,100);
            return $cache;
        }
    }
    function get_clientSettings(){
        global $settings;
        $name=$name=@file_get_contents($settings['name']);if($name===false){die("Error: NAME file was not found on the server or could not be read.");}
        $icon=$icon=@file_get_contents($settings['icon']);if($icon===false){die("Error: ICON file was not found on the server or could not be read.");}else{$icon='data:image/svg+xml;base64,'.base64_encode($icon);}
        return array(
            'name'             =>$name,
            'icon'             =>$icon,
            'maxFileUploadSize'=>max_file_upload_in_bytes()
        );
    }
    function get_plugins(){
        global $settings;
        // this function scans for valid plugins and returns an array:
		// [ ['pluginName'=> xxxxx, 'pluginDir'=> system_plugin_010 xxxxx ],
		//   ['pluginName'=> xxxxx, 'pluginDir'=> system_plugin_020 xxxxx ],
		//   ['pluginName'=> xxxxx, 'pluginDir'=> system_plugin_031 xxxxx ], …]
		$plugins=array();
        $files = @scandir($settings['rootDir']);if($files===false){die("Error: Could not scan root directory.");}
        foreach ($files as $file) {
            if (preg_match($settings['plugins_pattern'],$file,$matches)){
                $plugins[]=array('pluginName'=>$matches[1],'plugin'=> $matches[0]);
            }
        }
        return $plugins;
    }
    // Generic functions
    function get_timestamp($stdString=false){
        if($stdString){return  substr('0'.strval(round(microtime(true)*1000)),-14);}
        else{return round(microtime(true)*1000);}
    }
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
    function return_bits($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        if($val=='-1'){$val=0;}
        switch($last){
            case 'g':$val=intval(substr($val,0,-1))*1000000000;break;
            case 'm':$val=intval(substr($val,0,-1))*1000000;break;
            case 'k':$val=intval(substr($val,0,-1))*1000;break;
            default:$val=intval($val);
        }
        return $val;
    }
    function max_file_upload_in_bytes() {
        $max_upload = return_bits(ini_get('upload_max_filesize'));
        $max_post = return_bits(ini_get('post_max_size'));
        $memory_limit = return_bits(ini_get('memory_limit'));
        return min($max_upload, $max_post, $memory_limit);
    }
?>
