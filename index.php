<?php

/* Settings */

define('CARDDAV_HOST', 'https://domain.tld:8843/addressbooks/__uids__/7E097527-9AB7-4D4E-BA88-0ABA7080F268/addressbook/');
define('CARDDAV_USER', 'JohnDoe');
define('CARDDAV_PASS', 'afk0815');

// Groups are assigned by tags in the note field
$groups = array(
	'AP'=>2,
	'GF'=>10,
	'HAP'=>4,
	'HEGA'=>12,
	'HGF'=>11,
	'IAV'=>14,
	'KA'=>8,
	'MAG'=>13,
	'NL'=>6,
	'PMAQ'=>5,
	'QS'=>3,
	'VS'=>16
);

/* --------------------------------------------------------- */

require ('../../system/config/localconfig.php');
require ('lib.carddav.php');
require ('lib.vcard.php');

if ($_GET['action']=='run')
{
	// connect to source_database
	$db = mysql_connect($GLOBALS['TL_CONFIG']['dbHost'], $GLOBALS['TL_CONFIG']['dbUser'], $GLOBALS['TL_CONFIG']['dbPass']);
	if (!$db)
	{
		$messages[] = '<div class="alert alert-error">Can not connect to contao database!</div>';
		die;
	}
	mysql_select_db($GLOBALS['TL_CONFIG']['dbDatabase'], $db);

	// delete all entries imported by this script
	if ($_POST['testing_mode'] != '1')
	{
		mysql_query('DELETE FROM tl_member WHERE LENGTH(carddav_id) > 0') or die ($messages[] = '<div class="alert alert-error">MySQL-Error: '.mysql_error().'</div>');
	}

	// get contacts from apple address book server
	$carddav = new carddav_backend(CARDDAV_HOST);
 	$carddav->set_auth(CARDDAV_USER, CARDDAV_PASS);
 	if ($_POST['log_carddav']) $log[] = 'Carddav-Server Connection Status: '.print_r($carddav->check_connection(), true);
 	$cards = $carddav->get($include_vcards=false);
 	if ($_POST['log_carddav'])$log[] = $cards;

 	// parse xml
 	$xml = new SimpleXMLElement($cards);
 	foreach ($xml->element as $element)
 	{
 		if ($_POST['log_carddav']) $log[] = 'Load Element-ID: '.$element->id;

 		// parse vcard
 		$vcard = new vCard(false, $carddav->get_vcard($element->id));

 		// generate groups array (groups are assigned by tags in the note field)
 		unset($member_groups);
 		if (count($vcard->note))
 		{
	 		$member_groups = array();
	 		foreach ($groups as $key => $value) 
	 		{
	 			if (strpos($vcard->note[0], $key) !== false) array_push($member_groups, $value);
	 		}
	 	}

 		date_default_timezone_set('Europe/Berlin');
 		$last_modified = DateTime::createFromFormat('D, d M Y H:i:s e', $element->last_modified);
 	
		// insert
		$sql = 'INSERT INTO 
		   			tl_member 
			   	SET 
					tstamp='.$last_modified->getTimestamp().',
					dateAdded='.$last_modified->getTimestamp().',
					firstname="'.$vcard->n[0]['FirstName'].'",
					lastname="'.$vcard->n[0]['LastName'].'",
					company="'.$vcard->org[0]['Name'].'",
					street="'.$vcard->adr[0]['StreetAddress'].'",
					postal="'.$vcard->adr[0]['PostalCode'].'",
					city="'.$vcard->adr[0]['Locality'].'",
					phone="'.$vcard->tel[0]['Value'].'",
					email="'.$vcard->email[0]['Value'].'",
					login=1,
					username="'.$vcard->email[0]['Value'].'",
					password="'.md5(strtolower($vcard->n[0]['FirstName'].$vcard->n[0]['LastName'])).'",
					groups="'.(count($member_groups) ? serialize($member_groups) : '').'",
					activation=1,
					carddav_id="'.$element->id.'"';
 		

		if ($_POST['log_mysql']) $log[] = 'SQL: '.$sql;

		if ($_POST['testing_mode'] != '1' && count($member_groups))
		{
			$query = mysql_query($sql, $db) or die ($messages[] = '<div class="alert alert-error">MySQL-Error: '.mysql_error().'</div>');
			if ($query)
			{
				if ($_POST['log_mysql']) $log[] = 'Insert carddav_id: '.$element->id;
				$num++;
			}
		}

 	}

	if ($num > 1) $messages[] = '<div class="alert alert-success">Import finished. '.$num.' records successfully imported.</div>';
	if ($_GET['mode'] == 'cron') echo 'Import finished. '.$num.' records successfully imported.';

}

if (!$_GET['mode'] == 'cron') {

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>CardDAV Contao Member Sync</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
	<style type="text/css">
		body { position: relative; padding-top: 40px; }
		h1 { margin-bottom: 40px; }
		textarea { width: 100%; box-sizing: border-box; height: 180px; }
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			<h1>CardDAV Contao Member Sync</h1>
			<?php
			if (count($messages))
			{
				foreach ($messages as $message)
				{
					echo $message;
				}
			}
			?>

			<?php if (count($log)): ?>
			<h2>Log</h2>
			<textarea><?php
				foreach ($log as $message)
				{
					echo $message.chr(10);
				}
			?></textarea> 
			<?php endif ?>

			<h2>Sync settings</h2>
			<form method="post" action="?action=run">
				<div class="control-group">
					<div class="controls">
					  	<label class="checkbox">
				      	<input name="testing_mode" type="checkbox" value="1" checked="checked"> Testing mode (nothing stored in database)
				    	</label>
				    	<label class="checkbox">
				      	<input name="log_carddav" type="checkbox" value="1" checked="checked"> Log carddav
				    	</label>
				    	<label class="checkbox">
				      	<input name="log_mysql" type="checkbox" value="1" checked="checked"> Log mysql
				    	</label>
				    	<button type="submit" class="btn btn-large btn-primary">Sync</button>
				    </div>
				</div>
			</form>
			<p><i style="color: #999;"><strong>For using the script with a cron job, just add parameter mode=cron:</strong><br>
				http://domain.tld/_tools/sync_contacts/?action=run&mode=cron</i></p>
		</div>
	</div>
</body>
</html>
<?php } ?>