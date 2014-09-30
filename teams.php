<html>
	<head>
		<title>Equipos</title>
	</head>
	<body>
		<p id="demo"></p>
		<?php
			$TIME_ZONE = 'Europe/Madrid';
			date_default_timezone_set($TIME_ZONE);
			$file = 'teams.json';
			if(file_exists($file)){
				$dateFormat = 'd/m/Y';
				$actualDate = DateTime::createFromFormat('U', time());
				$fileModDate = DateTime::createFromFormat('U', filemtime($file));
				$interval = date_diff($actualDate, $fileModDate);
				
				$jsonfile = file_get_contents($file);
				$teams = json_decode($jsonfile);
				echo htmlentities('Última actualización: ') . $fileModDate->format($dateFormat) . '<br>';
				
				$fileModDate->add(new DateInterval('P1M'));
				if($actualDate > $fileModDate){
					updateFile();
				}
			} else {
				updateFile();
			}
			showTeams();
			
			function updateFile() {
				// Replace with your bot name and email/website to contact if there is a
				// problem e.g., "mybot/0.1 (https://erikberg.com/)"
				$USER_AGENT = 'xxxxxxxxxxxxxxxxxxxxx';
				// Replace with your access token
				$ACCESS_TOKEN = 'xxxxxxxxxxxxxxxxxxxxxxxxxx';
				
				$host   = 'erikberg.com';
				$sport  = 'nba';
				$method = 'teams';
				$id     = '';
				$format = 'json';
				
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
					$file = 'teams.json';
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
			
			function showTeams() {
				$headerHTML = '<!DOCTYPE html><html><head>
					<link href="style.css" rel="stylesheet" type="text/css">
					</head><body><h1>Equipos</h1>';

				$tableHTML = '<table id="teams">
					<tr>
					<td>Equipo</td>
					<td>Conferencia</td>
					<td>' . htmlentities('División') . '</td>
					<td>Campo</td>
					<td>Ciudad, Estado</td>
					</tr>';

				$rowHTML = ' <tr class="team">
					<td class="name">%s</td>
					<td class="conference">%s</td>
					<td class="division">%s</td>
					<td class="arena">%s</td>
					<td class="city-state">%s</td>
					</tr>';
        
				$fileJSON = 'teams.json';
				$jsonStr = file_get_contents($fileJSON);
				$teams = json_decode($jsonStr);
				
				$dateFormat = 'd/m/Y H:i:s';
				//$actualDate = DateTime::createFromFormat('U', time());
				
				for ($i = 0 ; $i < count($teams) ; $i++) {
					$team_name = '<a href=team-page.php?equipo=' . $teams[$i]->team_id . '>' . $teams[$i]->full_name . '</a>';
					$team_conference = getTeamConference($teams[$i]->conference);
					$team_division = getTeamDivision($teams[$i]->division);
					$team_arena = $teams[$i]->site_name;
					$team_city_state = $teams[$i]->city . ', ' . $teams[$i]->state;
					$active = $teams[$i]->active;
					
					$tableHTML .= sprintf($rowHTML, $team_name, $team_conference, $team_division, $team_arena, $team_city_state);
				}
				
				$tableHTML .= '</table></body></html>';
				
				$fileHTML = 'equipos.html';
				file_put_contents($fileHTML, $headerHTML.$tableHTML, LOCK_EX);
				
				echo $headerHTML.$tableHTML;
			}
			
			function getTeamConference($conference) {
				if ($conference === 'East') return 'Este';
				else return 'Oeste';
			}
			
			function getTeamDivision($division) {
				switch ($division) {
					case 'Northwest':
						return 'Noroeste';
					case 'Southeast':
						return 'Sureste';
					case 'Southwest':
						return 'Suroeste';
					case 'Atlantic':
						return htmlentities('Atlántico');
					case 'Pacific':
						return htmlentities('Pacífico');
					case 'Central':
						return 'Central';
					default:
						return 'Error';
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
		?>
	</body>
</html>