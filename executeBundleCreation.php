<?php
/* authenticate with soap */
require 'soap_config.php';

/* Generate random password */
function randomPassword($max) {
	//* http://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < $max; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass); //turn the array into a string
}

$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1));

/* Define user and domain vars */
$newDomain = 'test.com';
$newDb = str_replace(array('.'), '' , $newDomain);
$newUsername = 'tester'; 
$password_email = randomPassword(12);
$password_db = randomPassword(12); 
$password_ftp = randomPassword(12); 
$quota_hdd = '1000';
$quota_traffic = '1000';
$quota_mail = '500000000'; # 500000000 thats actually 500 mb ; dont know why
$client_id = 2; # modify with either the default admin user "0" or the one of your choice

try {
	if($session_id = $client->login($soapUsername, $soapPassword)) {
		echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	}
	
/* sites_web_domain_add 
 * 
 * db : dbispconfig -> web_domain 
 * 
 * */
	
	//* Set the function parameters.
	$paramsWebDomainAdd = array(
			'server_id' => 1,
			'ip_address' => '*',
			'domain' => $newDomain,
			'type' => 'vhost',
			'parent_domain_id' => 0, 
			'vhost_type' => 'name',
			'hd_quota' => $quota_hdd,
			'traffic_quota' => $quota_traffic,
			'cgi' => 'y',
			'ssi' => 'n',
			'suexec' => 'n',
			'errordocs' => 1,
			'is_subdomainwww' => 1,
			'subdomain' => '',
			'php' => 'fast-cgi', # fast-cgi , php-fpm or whatever php module you want ; examples initially show "y" as option, but it will appear as disabled
			'ruby' => 'n',
			'redirect_type' => '',
			'redirect_path' => '',
			'ssl' => 'n',
			'ssl_state' => '',
			'ssl_locality' => '',
			'ssl_organisation' => '',
			'ssl_organisation_unit' => '',
			'ssl_country' => '',
			'ssl_domain' => '',
			'ssl_request' => '',
			'ssl_key' => '',
			'ssl_cert' => '',
			'ssl_bundle' => '',
			'ssl_action' => '',
			'stats_password' => '',
			'stats_type' => 'webalizer',
			'allow_override' => 'All',
			'apache_directives' => '',
			'php_open_basedir' => '/',
			'pm_max_requests' => 0,
			'pm_process_idle_timeout' => 10,
			'custom_php_ini' => '',
			'backup_interval' => '',
			'backup_copies' => 1,
			'active' => 'y',
			'traffic_quota_lock' => 'n',
			'added_by' => $soapUsername
			# added_date in table is registered as 0000-00-00
	);
	
	// $affected_rows = ID in web_domain table / referenced as parent_domain_id
	$newDomainID = $client->sites_web_domain_add($session_id, $client_id, $paramsWebDomainAdd, $readonly = false);
	
	echo "Web Domain ID: ".$newDomainID."<br>";
	//*/
	
/* sites_ftp_user_add
 * 
 * db : dbispconfig ->  ftp_user
 *  
 *  */
	
	//*Set the function parameters.
	
	$paramsFTPUserAdd = array(
			'server_id' => 1,
			'parent_domain_id' => $newDomainID, #fix
			'username' => $newUsername,
			'password' => $password_ftp,
			'quota_size' => $quota_hdd,
			'active' => 'y',
			'uid' => 'web' . $newDomainID,
			'gid' => 'client' . $client_id,
			'dir' => '/var/www/clients/client' . $client_id . '/web' . $newDomainID,
			'quota_files' => -1,
			'ul_ratio' => -1,
			'dl_ratio' => -1,
			'ul_bandwidth' => -1,
			'dl_bandwidth' => -1
	);
	
	$newFTPUserID = $client->sites_ftp_user_add($session_id, $client_id, $paramsFTPUserAdd);
	
	echo "FTP User ID: ".$newFTPUserID."<br>";
	//*/
	
/* sites_database_user_add
 * 
 * db : dbispconfig -> web_database_user
 *  
 *  */

	//* Set the function parameters.
	$paramsDBUserAdd = array(
			'server_id' => 1,
			'database_user' => $newUsername,
			'database_password' => $password_db
	);
	
	$newDBUserID = $client->sites_database_user_add($session_id, $client_id, $paramsDBUserAdd);
	
	echo "Database User ID: ".$newDBUserID."<br>";
	//*/


/* sites_database_add 
 * 
 * db : dbispconfig -> web_database
 * 
 * 
 * */

	//* Set the function parameters.
	$paramsDBAdd = array(
			'server_id' => 1,
			'type' => 'mysql',
			'parent_domain_id' => $newDomainID, 
			'database_name' => $newDb, # The database name may contain these characters: a-z, A-Z, 0-9 and the underscore. Length: 2 - 64 characters.
			'database_user_id' => $newDBUserID, 
			'database_ro_user_id' => '0',
			'database_charset' => 'UTF8',
			'remote_access' => 'n',
			'remote_ips' => '',
			'backup_interval' => 'none',
			'backup_copies' => 1,
			'active' => 'y'
	);
	
	$newDBID = $client->sites_database_add($session_id, $client_id, $paramsDBAdd);
	
	echo "Database ID: ".$newDBID."<br>";
	//*/


/* mail_domain_add 
 * 
 * db : dbispconfig -> mail_domain
 * 
 * */

	//* Set the function parameters.
	$paramsMailDomainAdd = array(
			'server_id' => 1,
			'domain' => $newDomain,
			'active' => 'y'
	);
	
	$newMailDomainID = $client->mail_domain_add($session_id, $client_id, $paramsMailDomainAdd);
	
	echo "Mail Domain ID: ".$newMailDomainID."<br>";
	//*/
	
/* mail_user_add
 * 
 *  db : dbispconfig -> mail_user
 *  
 *  */

	//* Set the function parameters.
	$paramsMailUserAdd = array(
			'server_id' => 1,
			'email' => $newUsername . '@' . $newDomain,
			'login' => $newUsername . '@' . $newDomain,
			'password' => $password_email,
			'name' => $newUsername,
			'uid' => 5000, 
			'gid' => 5000, 
			'maildir' => '/var/vmail/' . $newDomain . '/' . $newUsername,
			'quota' => $quota_mail, 
			'cc' => '',
			'homedir' => '/var/vmail',
			'autoresponder' => 'n',
			'autoresponder_start_date' => '0000-00-00 00:00:00',
			'autoresponder_end_date' => '0000-00-00 00:00:00',
			'move_junk' => 'n',
			'postfix' => 'y',
			'access' => 'y',
			'disableimap' => 'n',
			'disablepop3' => 'n',
			'disabledeliver' => 'n',
			'disablesmtp' => 'n'
	);
	
	$newMailUserID = $client->mail_user_add($session_id, $client_id, $paramsMailUserAdd);
	
	echo "New user: ".$newMailUserID."<br>";
	//*/
	
	/* logging out */

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}
	
	/* show output to the user and eventually send mail with it */
	
	echo "Domain - " . $newDomain;
	echo "Can be visited at http://" . $newDomain;
	echo '</br>';
	echo "File Transfer Protocol - FTP Details";
	echo '</br>';
	echo "FTP Username - " . $newUsername;
	echo '</br>';
	echo "FTP Password - " . $password_ftp;
	echo '</br>';
	echo "FTP Host - " . $newDomain;
	echo '</br>';
	echo "Database Details";
	echo "Database Username - " . $newUsername;
	echo '</br>';
	echo "Database Password - " . $password_db;
	echo '</br>';
	echo "Database Host - " . $newDomain;
	echo '</br>';
	echo "Database Name - " . $newDb;
	echo '</br>';
	echo "Email Details";
	echo '</br>';
	echo "Email Username - " . $newUsername . '@' . $newDomain;
	echo '</br>';
	echo "Email Password - " . $password_email;
	echo '</br>';
	echo "Webmail location - " . $newDomain;

	
	
} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}
	




	