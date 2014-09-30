<html>
	<head>
		<title>Pr&oacute;ximos partidos</title>
	</head>
	<body>
		<p id="demo"></p>
		<?php 
			$TIME_ZONE = 'Europe/Madrid';
			date_default_timezone_set($TIME_ZONE);
			$file = 'events.json';
			if(file_exists($file)){
				$dateFormat = 'd/m/Y H:i:s';
				$actualDate = DateTime::createFromFormat('U', time());
				$fileModDate = DateTime::createFromFormat('U', filemtime($file));
				$interval = date_diff($actualDate, $fileModDate);
				
				$jsonfile = file_get_contents($file);
				$events = json_decode($jsonfile);
				$date = DateTime::createFromFormat(DateTime::W3C, $events->events_date);
				echo htmlentities('Última actualización: ') . $date->format($dateFormat) . '<br>';
				
				$fileModDate->add(new DateInterval('PT8H'));
				if($actualDate > $fileModDate){
					updateFile();
				}
			} else {
				updateFile();
			}
			showEvents();

			function updateFile() {
				// Replace with your bot name and email/website to contact if there is a
				// problem e.g., "mybot/0.1 (https://erikberg.com/)"
				$USER_AGENT = 'xxxxxxxxxxxxxxxxxxxxx';
				// Replace with your access token
				$ACCESS_TOKEN = 'xxxxxxxxxxxxxxxxxxxxxxxxxx';
				
				$host   = 'erikberg.com';
				$sport  = '';
				$method = 'events';
				$id     = '';
				$format = 'json';
				$parameters = array(
					'sport' => 'nba'
				);
				
				$url = buildURL($host, $sport, $method, $id, $format, $parameters);
				
				echo '<script type="text/javascript">'
					 , 'httpRequest('.$url.');'
					 , '</script>';
				
				// Set the User Agent, Authorization header and allow gzip
				$default_opts = array(
				'http' => array(
					'user_agent' => $USER_AGENT,
					'header'     => array(
						'Accept-Encoding: gzip',
						'Authorization: Bearer ' . $ACCESS_TOKEN
						)
					)
				);
				

				$default = stream_context_get_default($default_opts);
				libxml_set_streams_context($default);
				
				$data = 'compress.zlib://' . $url;
				$fh = fopen($data, 'rb');
				if ($fh && strpos($http_response_header[0], "200 OK") !== false) {
					$file = 'events.json';
					$content = stream_get_contents($fh);
					fclose($fh);
					file_put_contents($file, $content, LOCK_EX);
				} else {
				
					// handle error, check $http_response_header for HTTP status code, etc.
					foreach ($http_response_header as $thingy){
						echo $thingy.'<br>';
					}
					print "handle error...\n";
					echo "Handle error<br>";
				}
				
			}
			
			function buildURL($host, $sport, $method, $id, $format, $parameters){
				$ary = array($sport, $method, $id);
				$path = join('/', preg_grep('/^$/', $ary, PREG_GREP_INVERT));
				$url = 'https://' . $host . '/' . $path . '.' . $format;

				// Check for parameters and create parameter string
				if (!empty($parameters)) {
					$paramlist = array();
					foreach ($parameters as $key => $value) {
						array_push($paramlist, rawurlencode($key) . '=' . rawurlencode($value));
					}
					$paramstring = join('&', $paramlist);
					if (!empty($paramlist)) { $url .= '?' . $paramstring; }
				}
				return $url;
			}
			
			function showEvents(){
				$headerHTML = '<!DOCTYPE html><html><head>
					<link href="style.css" rel="stylesheet" type="text/css">
					</head><body><h1>' . htmlentities('Próximos Partidos') . '</h1>';

				$tableHTML = '<table id="events">
					<tr>
					<td>Hora</td>
					<td>Partido</td>
					<td>Estado</td>
					</tr>';

				$rowHTML = ' <tr class="event">
					<td class="time">%s</td>
					<td class="event">%s</td>
					<td class="status">%s</td>
					</tr>';
        
				$fileJSON = 'events.json';
				$jsonStr = file_get_contents($fileJSON);
				$events = json_decode($jsonStr);
				
				$dateFormat = 'd/m/Y H:i:s';
				//$actualDate = DateTime::createFromFormat('U', time());

				foreach ($events->event as $e) {
					$event_id = $e->event_id;
					$event_status = $e->event_status;
					$start_date_time = DateTime::createFromFormat(DateTime::W3C, $e->start_date_time);
					$away_team = $e->away_team->full_name;
					$home_team = $e->home_team->full_name;
					$teams_playing = $away_team . ' vs ' . $home_team;
					
					$tableHTML .= sprintf($rowHTML, $start_date_time->format($dateFormat), $teams_playing, $event_status);
				}
				
				$tableHTML .= '</table></body></html>';
				
				$fileHTML = 'partidos.html';
				file_put_contents($fileHTML, $headerHTML.$tableHTML, LOCK_EX);
				
				echo $headerHTML.$tableHTML;
			}
			
		?>
	</body>
</html>
