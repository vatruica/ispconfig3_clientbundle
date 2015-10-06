<?php
/* show some erros */
error_reporting(E_ALL);
ini_set( "display_errors", 1);

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

/* instantiate SOAP class */
require 'soap_config.php';

$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1));

/* Define user and domain vars */
$clientCompany = $_POST["clientcompany"]; 
$clientName = $_POST["clientname"];
$clientCity= "Odense";
$clientCountry = "DK";
$clientPhone = $_POST["clientphone"];
$clientEmail = $_POST["clientemail"];
$clientUsername = str_replace(array(' '), '' , $clientName);
$clientPassword = randomPassword(12);

$newDomain = $_POST["domainname"];
$newDb = str_replace(array('.'), '' , $newDomain); 
$newUsername = $_POST["domainusername"];
$password_email = randomPassword(12); 
$password_db = randomPassword(12); 
$password_ftp = randomPassword(12); 
$quota_hdd = '1000';
$quota_traffic = '1000';
$quota_mail = '500000000'; # 500000000 thats actually 500 mb ; dont know why ; check db table

$today = date("Y-m-d");


/* connect to ISPconfig and get session id */
try {
	if($session_id = $client->login($soapUsername, $soapPassword)) {
		echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	}
	
	
/*
 * client_add
 * 
 * db : dbispconfig -> client
 * 
 * 
 */
	
	$reseller_id = 0; // this id has to be 0 if the client shall not be assigned to admin or if the client is a reseller
	$paramsClientAdd = array(
			'company_name' => $clientCompany,
			'contact_name' => $clientName,
			'customer_no' => '', # fix - just blank for the moment
			'vat_id' => '',
			'street' => '',
			'zip' => '',
			'city' => $clientCity,
			'state' => '',
			'country' => $clientCountry,
			'telephone' => $clientPhone,
			'mobile' => $clientPhone,
			'fax' => '',
			'email' => $clientEmail,
			'internet' => '',
			'icq' => '',
			'notes' => '',
			'default_mailserver' => 1,
			'limit_maildomain' => 1,
			'limit_mailbox' => 5,
			'limit_mailalias' => 1,
			'limit_mailaliasdomain' => 1,
			'limit_mailforward' => 1,
			'limit_mailcatchall' => 1,
			'limit_mailrouting' => 0,
			'limit_mailfilter' => 5,
			'limit_fetchmail' => -1,
			'limit_mailquota' => 500,
			'limit_spamfilter_wblist' => 0,
			'limit_spamfilter_user' => 0,
			'limit_spamfilter_policy' => 1,
			'default_webserver' => 1,
			'limit_web_ip' => '',
			'limit_web_domain' => 1,
			'limit_web_quota' => 1000,
			'web_php_options' => 'no,fast-cgi,cgi,mod,suphp,php-fpm',
			'limit_web_subdomain' => 1,
			'limit_web_aliasdomain' => 1,
			'limit_ftp_user' => 2,
			'limit_shell_user' => 0,
			'ssh_chroot' => 'no,jailkit,ssh-chroot',
			'limit_webdav_user' => 0,
			'default_dnsserver' => 1,
			'limit_dns_zone' => 0,
			'limit_dns_slave_zone' => 0,
			'limit_dns_record' => 0,
			'default_dbserver' => 1,
			'limit_database' => 1,
			'limit_cron' => 0,
			'limit_cron_type' => 'url',
			'limit_cron_frequency' => 5,
			'limit_traffic_quota' => 1000,
			'limit_client' => 0, // If this value is > 0, then the client is a reseller
			'parent_client_id' => 0,
			'username' => $clientUsername,
			'password' => $clientPassword,
			'language' => 'en',
			'usertheme' => 'default',
			'template_master' => 0,
			'template_additional' => '',
			#'created_at' => 0
			'added_date' => $today
			);
	
	$clientID = $client->client_add($session_id, $reseller_id, $paramsClientAdd);
	
	// attributing the client id to the newly created
	$client_id = $clientID;
	
/* sites_web_domain_add 
 * 
 * db : dbispconfig -> web_domain 

the following are filled like below and not as defined by apache ; look into templates 
pm_max_children 1
pm_start_servers 1
pm_min_spare_Servers 1
pm_max_spare_servers 1
pm_process_idle_timeout 1
pm_max_Requests 10

 * */
	
	
	//* Set the function parameters.
	$paramsWebDomainAdd = array(
			'server_id' => 1,
			'ip_address' => '*',
			'domain' => $newDomain,
			'type' => 'vhost',
			'parent_domain_id' => 0, #fix $lastDomainID = "query in db for that" ; parent_domain_id = $lastDomainID + 1 
			'vhost_type' => 'name',
			'hd_quota' => $quota_hdd,
			'traffic_quota' => $quota_traffic,
			'cgi' => 'y',
			'ssi' => 'n',
			'suexec' => 'n',
			'errordocs' => 1,
			'is_subdomainwww' => 1,
			'subdomain' => '',
			'php' => 'y', #fix - its being registered as "disabled" ; instead of y add string of option : fast-cgi , php-fpm etc
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
			'added_date' => $today,
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
 * does not associate with website - website_id has changed into parent_domain_id
 * atributes default user the first one created, but does not show in options - user is now created first and added to params
 * 
 * */

	//* Set the function parameters.
	$paramsDBAdd = array(
			'server_id' => 1,
			'type' => 'mysql',
			'parent_domain_id' => $newDomainID, #fix
			'database_name' => $newDb, # The database name may contain these characters: a-z, A-Z, 0-9 and the underscore. Length: 2 - 64 characters.
			'database_user_id' => $newDBUserID, #fix
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
			'uid' => 5000, #fix
			'gid' => 5000, #fix
			'maildir' => '/var/vmail/' . $newDomain . '/' . $newUsername,
			'quota' => $quota_mail, # 500 ends up into - 0.00047683715820312 ; its a bigint(20)
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
	echo "</br></br>";
	echo "Client created with credentials : </br>";
	echo "Username " . $clientUsername . "</br>";
	echo "Password " . $clientPassword . "</br>";
	echo "ID : ".$clientID."<br>";
	echo "</br>";
	echo "Domain - " . $newDomain . "</br>";;
	echo "Can be visited at http://" . $newDomain . "</br>";;
	echo "File Transfer Protocol - FTP Details" . "</br>";;
	echo "FTP Username - " . $newUsername . "</br>";;
	echo "FTP Password - " . $password_ftp . "</br>";;
	echo "FTP Host - " . $newDomain . "</br>";;
	echo '</br>';
	echo "Database Details </br>";
	echo "Database Username - " . $newUsername . "</br>";;
	echo "Database Password - " . $password_db . "</br>";;
	echo "Database Host - " . $newDomain . "</br>";;
	echo "Database Name - " . $newDb . "</br>";;
	echo '</br>';
	echo "Email Details </br>";
	echo "Email Username - " . $newUsername . '@' . $newDomain . "</br>";;
	echo "Email Password - " . $password_email . "</br>";;
	echo "Webmail location - " . $newDomain . "</br>";;

	
	
} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}
	
