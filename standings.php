<html>
	<head>
		<title>Clasificaci&oacute;n</title>
	</head>
	<body>
		<?php 
			$TIME_ZONE = 'Europe/Madrid';
			date_default_timezone_set($TIME_ZONE);
			$file = 'standings.json';
			if(file_exists($file)){
				$dateFormat = 'd/m/Y H:i:s';
				$actualDate = DateTime::createFromFormat('U', time());
				$fileModDate = DateTime::createFromFormat('U', filemtime($file));
				$interval = date_diff($actualDate, $fileModDate);
				
				$jsonfile = file_get_contents($file);
				$standings = json_decode($jsonfile);
				$date = DateTime::createFromFormat(DateTime::W3C, $standings->standings_date);
				echo htmlentities('Últimos datos: ') . $date->format($dateFormat) . '<br>';
				
				$fileModDate->add(new DateInterval('PT8H'));
				if($actualDate > $fileModDate){
					updateFile();
				}
			} else {	// JSON no existe
				updateFile();
			}
			showStandings();

			function updateFile(){
				// Replace with your bot name and email/website to contact if there is a
				// problem e.g., "mybot/0.1 (https://erikberg.com/)"
				$USER_AGENT = 'xxxxxxxxxxxxxxxxxxxxx';
				// Replace with your access token
				$ACCESS_TOKEN = 'xxxxxxxxxxxxxxxxxxxxxxxxxx';
				
				$host   = 'erikberg.com';
				$sport  = 'nba';
				$method = 'standings';
				$id     = '';
				$format = 'json';
				$url = buildURL($host, $sport, $method, $id, $format, $parameters);
				
				echo '<script type="text/javascript">'
					 , 'httpRequest('.$url.');'
					 , '</script>';
				
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
			    $file = 'standings.json';
					$content = stream_get_contents($fh);
					fclose($fh);
					//echo 'Write to \''.$file.'\'<br><br>';
					file_put_contents($file, $content, LOCK_EX);
				} else {
					// handle error, check $http_response_header for HTTP status code, etc.
					/*foreach ($http_response_header as $thingy){
						echo $thingy.'<br>';
					}*/
					//print "handle error...\n";
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
			
			function showStandings(){
				$headerHTML = '<!DOCTYPE html><html><head>
				<link href="style.css" rel="stylesheet" type="text/css">
				</head><body><h1>' . htmlentities('Clasificación') . '</h1>';

				$tableHTML = '  <tr>
				<!--<td>Puesto</td>-->
				<td>Equipo</td>
				<td>V</td>
				<td>D</td>
				<td>PCT</td>
				<td>GB</td>
				<td>Casa</td>
				<td>Fuera</td>
				<td>Ultimos 10</td>
				<td>Racha</td>
				</tr>';

				$eastHTML = '<table id="clasificacion">
				<tr><td class="conferencia">Este</td></tr>' . $tableHTML;

				$westHTML = '<table id="clasificacion">
				<tr><td class="conferencia">Oeste</td></tr>' . $tableHTML;

				$rowHTML = ' <tr class="clasificacion-fila">
				<!--<td class="puesto">%d</td>-->
				<td class="equipo">%s</td>
				<td class="ganados">%d</td>
				<td class="perdidos">%d</td>
				<td class="pct">%.3F</td>
				<td class="gb">%d</td>
				<td class="casa">%s</td>
				<td class="fuera">%s</td>
				<td class="ultimos">%s</td>
				<td class="racha">%s</td>
				</tr>';
        
				$fileJSON = 'standings.json';
				$jsonStr = file_get_contents($fileJSON);
				$standings = json_decode($jsonStr);

				foreach ($standings->standing as $t) {
					$teamPCT = round(($t->won / ($t->won+$t->lost)), 3);
					if ($t->conference == 'EAST'){
						if ($t->streak_type == 'win') $streak = ' V';
						else $streak = ' D';
						$eastHTML .= sprintf($rowHTML, $t->rank, $t->first_name.' '.$t->last_name,
								$t->won, $t->lost, $teamPCT, $t->games_back, 
								$t->home_won.'-'.$t->home_lost,
								$t->away_won.'-'.$t->away_lost,
								$t->last_ten, $t->streak_total.$streak);
					}else{
						$westHTML .= sprintf($rowHTML, $t->rank, $t->first_name.' '.$t->last_name,
								$t->won, $t->lost, $teamPCT, $t->games_back, 
								$t->home_won.'-'.$t->home_lost,
								$t->away_won.'-'.$t->away_lost,
								$t->last_ten, $t->streak_total.$streak);
					}
				}
				
				$eastHTML .= '</table><br><br>';
				$westHTML .= '</table></body></html>';
				
				$fileHTML = 'clasificacion.html';
				file_put_contents($fileHTML, $headerHTML.$eastHTML.$westHTML, LOCK_EX);
				
				echo $headerHTML.$eastHTML.$westHTML;
			}
			
		?>
	</body>
</html>
