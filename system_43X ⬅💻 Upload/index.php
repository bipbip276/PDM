<?php
	// This quite simple plugin allows uploading files over http(s) to the DESKTOP folder
	// It is safe to use and does not require configuration
	// It might be convenient on mobile devices not permitting drag and drop on the main page
    //**************************************************************
    $DESKTOP='../DESKTOP/';
    $DESKTOPCache='../.DESKTOP/.cache.json';
    //**************************************************************
    $htmlRes="";
    if(isset($_GET['uploadFile'])){
        if(!is_dir($DESKTOP)){mkdir($DESKTOP);}
        $target_file = $DESKTOP .'/'.strval(round(microtime(true)*1000)).' '.basename($_FILES["fileToUpload"]["name"]);
        $res=move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file);
        if($res){
			if(file_exists($DESKTOPCache)){
				if (unlink($DESKTOPCache)){$htmlRes="File has been uploaded";}
				else{$htmlRes='Error: could not clear the cache';}
			}else{$htmlRes="File has been uploaded";}
		}else{$htmlRes="Error There was an error uploading your file.";}
    }
	$html='';
	$html.='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
	$html.='<html>';
	$html.=    '<head>';
	$html.=        '<meta charset="UTF-8">';
	$html.=        '<title>Upload</title>';
	$html.=        '<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns=\'http://www.w3.org/2000/svg\'%20viewBox=\'0%200%2016%2016\'%3E%3Ctext%20x=\'0\'%20y=\'14\'%3E📤%3C/text%3E%3C/svg%3E" type="image/svg+xml" />';
	$html.=    '</head>';
	$html.=    '<body>';
	$html.=        'Upload (maximum size: '.human_filesize(max_file_upload_in_bytes()).'): <br/>';
	$html.=        '<form action="?uploadFile=true" method="post" enctype="multipart/form-data">';
	$html.=            '<input type="file" name="fileToUpload" id="fileToUpload"><br><br>';
	$html.=            '<input type="submit" value="Upload File" name="submit">';
	$html.=        '</form>';
	$html.=        '<hr/>';
	$html.=        'Result: <br/>'.$htmlRes.'';
	$html.=    '</body>';
	$html.='</html>';
    echo $html;
	exit(0);

    function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last){case 'g':$val *= 1024;case 'm':$val *= 1024;case 'k':$val *= 1024;}
        return $val;
    }
    function max_file_upload_in_bytes() {
        $max_upload = return_bytes(ini_get('upload_max_filesize'));
        $max_post = return_bytes(ini_get('post_max_size'));
        $memory_limit = return_bytes(ini_get('memory_limit'));
        return min($max_upload, $max_post, $memory_limit);
    }
    function human_filesize($size, $precision = 2) {
		$units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
		$step = 1024;
		$i = 0;
		while (($size / $step) > 0.9) {$size = $size / $step;$i++;}
		return round($size, $precision).$units[$i];
	}
?>
