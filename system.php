<?php
  $settings['rootDir']   = dirname(__FILE__). '/';
  $settings['systemDir'] = 'system/';
  $settings['password']  = 'PSWD.txt';
  $settings['icon']      = 'ICON.svg';
  $settings['name']      = 'NAME.txt';  
  //******************************************
  // Developpment settings
  // ini_set('display_errors','On');
  date_default_timezone_set('Europe/Paris');
  ini_set('session.cookie_lifetime', 86400);
  ini_set('session.gc_maxlifetime', 86400);
  ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT'])).'/../session');
  //****************************************
    session_start();
	
	//Destroy sessions unused for 24h
	//if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 86400)) {
	//	session_unset();     // unset $_SESSION variable for the run-time 
	//	session_destroy();   // destroy session data in storage
	//}
	//$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

	//Destroy sessions created more than 24h ago
	//if (!isset($_SESSION['CREATED'])) {
	//	$_SESSION['CREATED'] = time();
	//} else if (time() - $_SESSION['CREATED'] > 86400) {
	//	session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
	//	$_SESSION['CREATED'] = time();  // update creation time
	//}

	//Destroy sessions created yesterday
	if (!isset($_SESSION['CREATED'])) {
		$_SESSION['CREATED'] = time();
	}
	if ($_SESSION['CREATED'] > new DateTime('yesterday midnight')) {
		if(!isset($_SESSION['loggedIn'])){
			if(isset($_POST['password'])){
				if(base64_decode($_POST['password'])===trim(file_get_contents($settings['password']))){
					//Note about trim: see POSIX standard 3.206 a text line ends with <newline>
					unset($_POST);
					$_SESSION['loggedIn']='1';
					echo 'OK';
				}
				else{
					sleep(3);
					echo 'NOK';
				}
			}
			else{
				prompt(file_get_contents($settings['name']),file_get_contents($settings['icon']));
			}
		}
		else{
			if(isset($_GET['logout'])){
				session_unset();
				session_destroy();
				header('Location: /');
			}
			else{
				session_write_close();
				chdir($settings['systemDir']);
				require_once('index.php');
			}
		}
	}
	else{
		session_unset();
		session_destroy();
		header('Location: /');
	}



   

    
    function prompt($title='PDM',$icon="<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><text x='0' y='14'>🔒</text></svg>"){
        echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>
<html>
  <head>
    <META http-equiv='Content-Type' content='text/html; charset=utf-8'>
    <title>".$title."</title>
    <link rel='icon' href='data:image/svg+xml;base64,".base64_encode($icon)."'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <script type='text/javascript'>
      login_arrow='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgo8c3ZnCiAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgeG1sbnM6Y2M9Imh0dHA6Ly93ZWIucmVzb3VyY2Uub3JnL2NjLyIKICAgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIgogICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIgogICB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIgogICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL2lua3NjYXBlLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiCiAgIHhtbG5zOmlua3NjYXBlPSJodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy9uYW1lc3BhY2VzL2lua3NjYXBlIgogICBzb2RpcG9kaTpkb2NuYW1lPSJnby1uZXh0LnN2ZyIKICAgc29kaXBvZGk6ZG9jYmFzZT0iL2hvbWUvdGlnZXJ0L2N2cy9mcmVlZGVza3RvcC5vcmcvdGFuZ28taWNvbi10aGVtZS9zY2FsYWJsZS9hY3Rpb25zIgogICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjQzK2RldmVsIgogICBzb2RpcG9kaTp2ZXJzaW9uPSIwLjMyIgogICBpZD0ic3ZnMTEzMDAiCiAgIGhlaWdodD0iNDgiCiAgIHdpZHRoPSI0OCIKICAgaW5rc2NhcGU6ZXhwb3J0LWZpbGVuYW1lPSIvaG9tZS9qaW1tYWMvRGVza3RvcC93aS1maS5wbmciCiAgIGlua3NjYXBlOmV4cG9ydC14ZHBpPSI5MC4wMDAwMDAiCiAgIGlua3NjYXBlOmV4cG9ydC15ZHBpPSI5MC4wMDAwMDAiCiAgIHZlcnNpb249IjEuMCIKICAgaW5rc2NhcGU6b3V0cHV0X2V4dGVuc2lvbj0ib3JnLmlua3NjYXBlLm91dHB1dC5zdmcuaW5rc2NhcGUiPgogIDxkZWZzCiAgICAgaWQ9ImRlZnMzIj4KICAgIDxsaW5lYXJHcmFkaWVudAogICAgICAgaWQ9ImxpbmVhckdyYWRpZW50MjU5MSI+CiAgICAgIDxzdG9wCiAgICAgICAgIHN0eWxlPSJzdG9wLWNvbG9yOiM3M2QyMTYiCiAgICAgICAgIG9mZnNldD0iMCIKICAgICAgICAgaWQ9InN0b3AyNTkzIiAvPgogICAgICA8c3RvcAogICAgICAgICBzdHlsZT0ic3RvcC1jb2xvcjojNGU5YTA2IgogICAgICAgICBvZmZzZXQ9IjEuMDAwMDAwMCIKICAgICAgICAgaWQ9InN0b3AyNTk1IiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudAogICAgICAgaWQ9ImxpbmVhckdyYWRpZW50ODY2MiIKICAgICAgIGlua3NjYXBlOmNvbGxlY3Q9ImFsd2F5cyI+CiAgICAgIDxzdG9wCiAgICAgICAgIGlkPSJzdG9wODY2NCIKICAgICAgICAgb2Zmc2V0PSIwIgogICAgICAgICBzdHlsZT0ic3RvcC1jb2xvcjojMDAwMDAwO3N0b3Atb3BhY2l0eToxOyIgLz4KICAgICAgPHN0b3AKICAgICAgICAgaWQ9InN0b3A4NjY2IgogICAgICAgICBvZmZzZXQ9IjEiCiAgICAgICAgIHN0eWxlPSJzdG9wLWNvbG9yOiMwMDAwMDA7c3RvcC1vcGFjaXR5OjA7IiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxsaW5lYXJHcmFkaWVudAogICAgICAgaWQ9ImxpbmVhckdyYWRpZW50ODY1MCIKICAgICAgIGlua3NjYXBlOmNvbGxlY3Q9ImFsd2F5cyI+CiAgICAgIDxzdG9wCiAgICAgICAgIGlkPSJzdG9wODY1MiIKICAgICAgICAgb2Zmc2V0PSIwIgogICAgICAgICBzdHlsZT0ic3RvcC1jb2xvcjojZmZmZmZmO3N0b3Atb3BhY2l0eToxOyIgLz4KICAgICAgPHN0b3AKICAgICAgICAgaWQ9InN0b3A4NjU0IgogICAgICAgICBvZmZzZXQ9IjEiCiAgICAgICAgIHN0eWxlPSJzdG9wLWNvbG9yOiNmZmZmZmY7c3RvcC1vcGFjaXR5OjA7IiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDxyYWRpYWxHcmFkaWVudAogICAgICAgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiCiAgICAgICBncmFkaWVudFRyYW5zZm9ybT0ibWF0cml4KDIuMDQ2NzI5LC0zLjc0OTQyN2UtMTYsMi44NTM0MDRlLTE2LDEuNTU3NjEwLC0xOS41MTc5OSwzLjQ1MjA4NikiCiAgICAgICByPSIxNy4xNzE0MTUiCiAgICAgICBmeT0iMi44OTY5MzgxIgogICAgICAgZng9IjE5LjcwMTE0MSIKICAgICAgIGN5PSIyLjg5NjkzODEiCiAgICAgICBjeD0iMTkuNzAxMTQxIgogICAgICAgaWQ9InJhZGlhbEdyYWRpZW50ODY1NiIKICAgICAgIHhsaW5rOmhyZWY9IiNsaW5lYXJHcmFkaWVudDg2NTAiCiAgICAgICBpbmtzY2FwZTpjb2xsZWN0PSJhbHdheXMiIC8+CiAgICA8cmFkaWFsR3JhZGllbnQKICAgICAgIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIgogICAgICAgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgxLjAwMDAwMCwwLjAwMDAwMCwwLjAwMDAwMCwwLjUzNjcyMywyLjUxMTAxMmUtMTUsMTYuODczMDYpIgogICAgICAgcj0iMTUuNjQ0NzM3IgogICAgICAgZnk9IjM2LjQyMTEyNyIKICAgICAgIGZ4PSIyNC44MzcxMjYiCiAgICAgICBjeT0iMzYuNDIxMTI3IgogICAgICAgY3g9IjI0LjgzNzEyNiIKICAgICAgIGlkPSJyYWRpYWxHcmFkaWVudDg2NjgiCiAgICAgICB4bGluazpocmVmPSIjbGluZWFyR3JhZGllbnQ4NjYyIgogICAgICAgaW5rc2NhcGU6Y29sbGVjdD0iYWx3YXlzIiAvPgogICAgPHJhZGlhbEdyYWRpZW50CiAgICAgICBpbmtzY2FwZTpjb2xsZWN0PSJhbHdheXMiCiAgICAgICB4bGluazpocmVmPSIjbGluZWFyR3JhZGllbnQyNTkxIgogICAgICAgaWQ9InJhZGlhbEdyYWRpZW50MjU5NyIKICAgICAgIGN4PSIyMi4yOTE2MzYiCiAgICAgICBjeT0iMzIuNzk3NTEyIgogICAgICAgZng9IjIyLjI5MTYzNiIKICAgICAgIGZ5PSIzMi43OTc1MTIiCiAgICAgICByPSIxNi45NTYyIgogICAgICAgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgwLjg0MzAyMiwxLjg3MTg4NWUtMTYsLTIuMjY1MjI4ZS0xNiwxLjAyMDE2OCw0LjQ5OTI5OCwxLjM4MTk5MikiCiAgICAgICBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgLz4KICA8L2RlZnM+CiAgPHNvZGlwb2RpOm5hbWVkdmlldwogICAgIGlua3NjYXBlOndpbmRvdy15PSIyNSIKICAgICBpbmtzY2FwZTp3aW5kb3cteD0iMCIKICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSI4ODUiCiAgICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIxMjgwIgogICAgIGlua3NjYXBlOnNob3dwYWdlc2hhZG93PSJmYWxzZSIKICAgICBpbmtzY2FwZTpkb2N1bWVudC11bml0cz0icHgiCiAgICAgaW5rc2NhcGU6Z3JpZC1iYm94PSJ0cnVlIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJsYXllcjEiCiAgICAgaW5rc2NhcGU6Y3k9IjI3LjM5ODg3NiIKICAgICBpbmtzY2FwZTpjeD0iMzQuODI3NTUyIgogICAgIGlua3NjYXBlOnpvb209IjExLjMxMzcwOCIKICAgICBpbmtzY2FwZTpwYWdlc2hhZG93PSIyIgogICAgIGlua3NjYXBlOnBhZ2VvcGFjaXR5PSIwLjAiCiAgICAgYm9yZGVyb3BhY2l0eT0iMC4yNTQ5MDE5NiIKICAgICBib3JkZXJjb2xvcj0iIzY2NjY2NiIKICAgICBwYWdlY29sb3I9IiNmZmZmZmYiCiAgICAgaWQ9ImJhc2UiCiAgICAgZmlsbD0iIzRlOWEwNiIKICAgICBzdHJva2U9IiM0ZTlhMDYiIC8+CiAgPG1ldGFkYXRhCiAgICAgaWQ9Im1ldGFkYXRhNCI+CiAgICA8cmRmOlJERj4KICAgICAgPGNjOldvcmsKICAgICAgICAgcmRmOmFib3V0PSIiPgogICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2Uvc3ZnK3htbDwvZGM6Zm9ybWF0PgogICAgICAgIDxkYzp0eXBlCiAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vcHVybC5vcmcvZGMvZGNtaXR5cGUvU3RpbGxJbWFnZSIgLz4KICAgICAgICA8ZGM6Y3JlYXRvcj4KICAgICAgICAgIDxjYzpBZ2VudD4KICAgICAgICAgICAgPGRjOnRpdGxlPkpha3ViIFN0ZWluZXI8L2RjOnRpdGxlPgogICAgICAgICAgPC9jYzpBZ2VudD4KICAgICAgICA8L2RjOmNyZWF0b3I+CiAgICAgICAgPGRjOnNvdXJjZT5odHRwOi8vamltbWFjLm11c2ljaGFsbC5jejwvZGM6c291cmNlPgogICAgICAgIDxjYzpsaWNlbnNlCiAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vY3JlYXRpdmVjb21tb25zLm9yZy9saWNlbnNlcy9ieS1zYS8yLjAvIiAvPgogICAgICAgIDxkYzp0aXRsZT5HbyBOZXh0PC9kYzp0aXRsZT4KICAgICAgICA8ZGM6c3ViamVjdD4KICAgICAgICAgIDxyZGY6QmFnPgogICAgICAgICAgICA8cmRmOmxpPmdvPC9yZGY6bGk+CiAgICAgICAgICAgIDxyZGY6bGk+bmV4dDwvcmRmOmxpPgogICAgICAgICAgICA8cmRmOmxpPnJpZ2h0PC9yZGY6bGk+CiAgICAgICAgICAgIDxyZGY6bGk+YXJyb3c8L3JkZjpsaT4KICAgICAgICAgICAgPHJkZjpsaT5wb2ludGVyPC9yZGY6bGk+CiAgICAgICAgICAgIDxyZGY6bGk+Jmd0OzwvcmRmOmxpPgogICAgICAgICAgPC9yZGY6QmFnPgogICAgICAgIDwvZGM6c3ViamVjdD4KICAgICAgPC9jYzpXb3JrPgogICAgICA8Y2M6TGljZW5zZQogICAgICAgICByZGY6YWJvdXQ9Imh0dHA6Ly9jcmVhdGl2ZWNvbW1vbnMub3JnL2xpY2Vuc2VzL2J5LXNhLzIuMC8iPgogICAgICAgIDxjYzpwZXJtaXRzCiAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vd2ViLnJlc291cmNlLm9yZy9jYy9SZXByb2R1Y3Rpb24iIC8+CiAgICAgICAgPGNjOnBlcm1pdHMKICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly93ZWIucmVzb3VyY2Uub3JnL2NjL0Rpc3RyaWJ1dGlvbiIgLz4KICAgICAgICA8Y2M6cmVxdWlyZXMKICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly93ZWIucmVzb3VyY2Uub3JnL2NjL05vdGljZSIgLz4KICAgICAgICA8Y2M6cmVxdWlyZXMKICAgICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly93ZWIucmVzb3VyY2Uub3JnL2NjL0F0dHJpYnV0aW9uIiAvPgogICAgICAgIDxjYzpwZXJtaXRzCiAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vd2ViLnJlc291cmNlLm9yZy9jYy9EZXJpdmF0aXZlV29ya3MiIC8+CiAgICAgICAgPGNjOnJlcXVpcmVzCiAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vd2ViLnJlc291cmNlLm9yZy9jYy9TaGFyZUFsaWtlIiAvPgogICAgICA8L2NjOkxpY2Vuc2U+CiAgICA8L3JkZjpSREY+CiAgPC9tZXRhZGF0YT4KICA8ZwogICAgIGlua3NjYXBlOmdyb3VwbW9kZT0ibGF5ZXIiCiAgICAgaW5rc2NhcGU6bGFiZWw9IkxheWVyIDEiCiAgICAgaWQ9ImxheWVyMSI+CiAgICA8cGF0aAogICAgICAgdHJhbnNmb3JtPSJtYXRyaXgoMS4yNzExODYsMC4wMDAwMDAsMC4wMDAwMDAsMS4yNzExODYsLTguMTE5Mzc2LC0xNS4xMDE3OSkiCiAgICAgICBkPSJNIDQwLjQ4MTg2MyAzNi40MjExMjcgQSAxNS42NDQ3MzcgOC4zOTY4OTM1IDAgMSAxICA5LjE5MjM4ODUsMzYuNDIxMTI3IEEgMTUuNjQ0NzM3IDguMzk2ODkzNSAwIDEgMSAgNDAuNDgxODYzIDM2LjQyMTEyNyB6IgogICAgICAgc29kaXBvZGk6cnk9IjguMzk2ODkzNSIKICAgICAgIHNvZGlwb2RpOnJ4PSIxNS42NDQ3MzciCiAgICAgICBzb2RpcG9kaTpjeT0iMzYuNDIxMTI3IgogICAgICAgc29kaXBvZGk6Y3g9IjI0LjgzNzEyNiIKICAgICAgIGlkPSJwYXRoODY2MCIKICAgICAgIHN0eWxlPSJvcGFjaXR5OjAuMjk5NDY1MjI7Y29sb3I6IzAwMDAwMDtmaWxsOnVybCgjcmFkaWFsR3JhZGllbnQ4NjY4KTtmaWxsLW9wYWNpdHk6MTtmaWxsLXJ1bGU6ZXZlbm9kZDtzdHJva2U6bm9uZTtzdHJva2Utd2lkdGg6MTtzdHJva2UtbGluZWNhcDpidXR0O3N0cm9rZS1saW5lam9pbjptaXRlcjttYXJrZXI6bm9uZTttYXJrZXItc3RhcnQ6bm9uZTttYXJrZXItbWlkOm5vbmU7bWFya2VyLWVuZDpub25lO3N0cm9rZS1taXRlcmxpbWl0OjEwO3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2UtZGFzaG9mZnNldDowO3N0cm9rZS1vcGFjaXR5OjE7dmlzaWJpbGl0eTp2aXNpYmxlO2Rpc3BsYXk6aW5saW5lO292ZXJmbG93OnZpc2libGUiCiAgICAgICBzb2RpcG9kaTp0eXBlPSJhcmMiIC8+CiAgICA8cGF0aAogICAgICAgc29kaXBvZGk6bm9kZXR5cGVzPSJjY2NjY2NjYyIKICAgICAgIGlkPSJwYXRoODY0MyIKICAgICAgIGQ9Ik0gOC41NTQxODc1LDE1LjUxNzM0OCBMIDguNTU0MTg3NSwzMi41MTE3NjggTCAyMS41MzgsMzIuNTExNzY4IEwgMjEuNTM4LDQxLjA1NjgwNiBMIDQxLjQ5NzgzNSwyNC4xNTAzNjUgTCAyMS40MTkxOSw3LjEyNTExNjggTCAyMS40MTkxOSwxNS41MjI2NTIgTCA4LjU1NDE4NzUsMTUuNTE3MzQ4IHogIgogICAgICAgc3R5bGU9Im9wYWNpdHk6MTtjb2xvcjojMDAwMDAwO2ZpbGw6dXJsKCNyYWRpYWxHcmFkaWVudDI1OTcpO2ZpbGwtb3BhY2l0eToxO2ZpbGwtcnVsZTpldmVub2RkO3N0cm9rZTojM2E3MzA0O3N0cm9rZS13aWR0aDoxLjAwMDAwMDM2O3N0cm9rZS1saW5lY2FwOnJvdW5kO3N0cm9rZS1saW5lam9pbjpyb3VuZDttYXJrZXI6bm9uZTttYXJrZXItc3RhcnQ6bm9uZTttYXJrZXItbWlkOm5vbmU7bWFya2VyLWVuZDpub25lO3N0cm9rZS1taXRlcmxpbWl0OjEwO3N0cm9rZS1kYXNoYXJyYXk6bm9uZTtzdHJva2UtZGFzaG9mZnNldDowO3N0cm9rZS1vcGFjaXR5OjE7dmlzaWJpbGl0eTp2aXNpYmxlO2Rpc3BsYXk6aW5saW5lO292ZXJmbG93OnZpc2libGUiIC8+CiAgICA8cGF0aAogICAgICAgc29kaXBvZGk6bm9kZXR5cGVzPSJjY2NjY2MiCiAgICAgICBpZD0icGF0aDg2NDUiCiAgICAgICBkPSJNIDIxLjk2MjM4NSw4LjI0ODUwMzMgTCAyMS45NjIzODUsMTYuMDU0OTc4IEwgOS4xNDUyMTUxLDE2LjA1NDk3OCBMIDkuMTQ1MjE1MSwyNS4wOTU2OTEgQyAyNi44OTUyMTUsMjcuMDk1NjkxIDI1Ljc3ODc1MiwxNy42NDA0MDMgNDAuNTI4NzUyLDI0LjE0MDQwMyBMIDIxLjk2MjM4NSw4LjI0ODUwMzMgeiAiCiAgICAgICBzdHlsZT0ib3BhY2l0eTowLjUwODAyMTQ7Y29sb3I6IzAwMDAwMDtmaWxsOnVybCgjcmFkaWFsR3JhZGllbnQ4NjU2KTtmaWxsLW9wYWNpdHk6MTtmaWxsLXJ1bGU6ZXZlbm9kZDtzdHJva2U6bm9uZTtzdHJva2Utd2lkdGg6MTtzdHJva2UtbGluZWNhcDpyb3VuZDtzdHJva2UtbGluZWpvaW46cm91bmQ7bWFya2VyOm5vbmU7bWFya2VyLXN0YXJ0Om5vbmU7bWFya2VyLW1pZDpub25lO21hcmtlci1lbmQ6bm9uZTtzdHJva2UtbWl0ZXJsaW1pdDoxMDtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLWRhc2hvZmZzZXQ6MDtzdHJva2Utb3BhY2l0eToxO3Zpc2liaWxpdHk6dmlzaWJsZTtkaXNwbGF5OmlubGluZTtvdmVyZmxvdzp2aXNpYmxlIiAvPgogICAgPHBhdGgKICAgICAgIHN0eWxlPSJvcGFjaXR5OjAuNDgxMjgzMzk7Y29sb3I6IzAwMDAwMDtmaWxsOm5vbmU7ZmlsbC1vcGFjaXR5OjE7ZmlsbC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlOiNmZmZmZmY7c3Ryb2tlLXdpZHRoOjEuMDAwMDAwMzY7c3Ryb2tlLWxpbmVjYXA6YnV0dDtzdHJva2UtbGluZWpvaW46bWl0ZXI7bWFya2VyOm5vbmU7bWFya2VyLXN0YXJ0Om5vbmU7bWFya2VyLW1pZDpub25lO21hcmtlci1lbmQ6bm9uZTtzdHJva2UtbWl0ZXJsaW1pdDoxMDtzdHJva2UtZGFzaGFycmF5Om5vbmU7c3Ryb2tlLWRhc2hvZmZzZXQ6MDtzdHJva2Utb3BhY2l0eToxO3Zpc2liaWxpdHk6dmlzaWJsZTtkaXNwbGF5OmlubGluZTtvdmVyZmxvdzp2aXNpYmxlIgogICAgICAgZD0iTSA5LjUzNzcwMiwxNi41NjE4OTIgTCA5LjUzNzcwMiwzMS41NDYzMzIgTCAyMi41MjMwNjksMzEuNTQ2MzMyIEwgMjIuNTIzMDY5LDM4Ljk0MTQ5OCBMIDQwLjAwMTA4MywyNC4xNDU4MDcgTCAyMi41MDcxMDgsOS4zNjU0MDY2IEwgMjIuNTA3MTA4LDE2LjU2Njc4OSBMIDkuNTM3NzAyLDE2LjU2MTg5MiB6ICIKICAgICAgIGlkPSJwYXRoODY1OCIKICAgICAgIHNvZGlwb2RpOm5vZGV0eXBlcz0iY2NjY2NjY2MiIC8+CiAgPC9nPgo8L3N2Zz4K';
      login_clock='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB3aWR0aD0nMTIwcHgnIGhlaWdodD0nMTIwcHgnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIiBjbGFzcz0idWlsLWNsb2NrIj4KICA8cmVjdCB4PSIwIiB5PSIwIiB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0ibm9uZSIgY2xhc3M9ImJrIj48L3JlY3Q+CiAgPGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iMzAiIGZpbGw9IiNkNmZmZGQiIHN0cm9rZT0iIzJiYmEzOSIgc3Ryb2tlLXdpZHRoPSI4cHgiPjwvY2lyY2xlPgogIDxsaW5lIHgxPSI1MCIgeTE9IjUwIiB4Mj0iNTAiIHkyPSIzMCIgc3Ryb2tlPSIjMDAwIiBzdHJva2Utd2lkdGg9IjVweCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIj4KICAgIDxhbmltYXRlVHJhbnNmb3JtIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgdHlwZT0icm90YXRlIiBmcm9tPSIwIDUwIDUwIiB0bz0iMzYwIDUwIDUwIiBkdXI9IjVzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSI+PC9hbmltYXRlVHJhbnNmb3JtPgogIDwvbGluZT4KICA8bGluZSB4MT0iNTAiIHkxPSI1MCIgeDI9IjUwIiB5Mj0iMjAiIHN0cm9rZT0iI2YwMCIgc3Ryb2tlLXdpZHRoPSIycHgiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCI+CiAgICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InJvdGF0ZSIgZnJvbT0iMCA1MCA1MCIgdG89IjM2MCA1MCA1MCIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiPjwvYW5pbWF0ZVRyYW5zZm9ybT4KICA8L2xpbmU+Cjwvc3ZnPg==';
      window.onload=function(){document.getElementById('login_submit').src=window.login_arrow;};
      function submitLogin(){
        document.getElementById('password').disabled=true;
        document.getElementById('login_submit').src=window.login_clock;
        var req = new XMLHttpRequest();
        const formdata = new FormData();
        formdata.append('password',btoa(document.getElementById('password').value));
        req.open('POST','',true);
        req.onreadystatechange=function(){
            if (req.readyState==4){
                if(req.status==200){
                    switch(req.responseText){
                        case 'NOK':
                        document.getElementById('password').value='';
                        document.getElementById('password').placeholder='please try again';
                        document.getElementById('password').disabled=false;
                        document.getElementById('password').focus();
                        document.getElementById('login_submit').src=window.login_arrow;
                        break;
                        case 'OK':
                        location.reload();
                        break;
                        default:location.reload();//alert(req.responseText); (the latte option is to replace the former for debug)
                    }
                }else{alert('Something went wrong with the login request:' +req.status);}
            }
        }
        req.send(formdata);
      }
    </script>
  </head>
  <body style='margin:0;padding:0;background-color:grey;user-select:none;-webkit-tap-highlight-color:rgba(255,255,255,0);'>
    <table style='margin:auto;height:100%;width:100%;border-collapse: collapse;'>
      <tr>
        <td style='vertical-align:middle;text-align:center;'>
            <input type='password' name='password' id='password' autocomplete='on' autofocus=autofocus onblur='this.focus();' onkeypress='if(event.keyCode==13){submitLogin();}' style='outline: none;vertical-align:middle;height:1em;width:100%;max-width:10em;border:0;padding:1em 0.4em;font-size:2rem;'/>
            <img id='login_submit' height='100' width='100' alt='Request Access' onclick='submitLogin();' src='' style='vertical-align:middle;cursor:pointer;'/>
        </td>
      </tr>
    </table>
  </body>
</html>
";
    }
?>
