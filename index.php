<?php
if (!isset($_POST['payload'])) {
	http_response_code(401);
	die();
}

require 'ftp.php';
require 'config.php';

function verifyDir($conn, $add) {
	$dir = explode("/", $add);
	array_pop($dir);
	$makedir = ".";
	foreach ($dir as $d) {
		$makedir .= "/" . $d;
		$conn->ftp_mkdir($makedir);
	}
}

function verifyDirDeletion($conn, $add) {
	$dir = explode("/", $add);
	array_pop($dir);
	for ($i = count($dir); $i > 0; $i--) {
		$curdir = implode('/', array_slice($dir, 0, $i));
		if ( ($files = $conn->ftp_nlist($curdir)) != false ) {
			if (count($files) <= 2) {
				$conn->ftp_rmdir($curdir);
				echo "Removed directory " . $curdir . "\n";
			}
		}
	}
}

$payload = json_decode($_POST['payload'], true);
$before = $payload['before'];
$longid = $payload['head_commit']['id'];

/* Check whether this webhook accepts requests from this repository */
if (
	isset($configs[$payload['repository']['full_name']]) && 
	isset($configs[$payload['repository']['full_name']][$payload['ref']])
) {
	$repConfig = $configs[$payload['repository']['full_name']][$payload['ref']];
} else {
	http_response_code(404);
	die("This webhook has no configuration for " . $payload['repository']['full_name'] . "/" . $payload['ref']);
}

/* Security check */
$headers = getallheaders();
$localhash = "sha1=".hash_hmac("sha1", file_get_contents('php://input'), $repConfig['secret']);
$remotehash = $headers['X-Hub-Signature'];
if (!isset($repConfig['secret'])) {
	http_response_code(401);
	die("It is strongly advised, in fact enforced in this version of this webhook, to use a secret to verify the authenticity of requests.\n");
}
if ($localhash != $remotehash) {
	http_response_code(401);
	die();
}


/* Connect to the FTP server */
$conn = new ftp($repConfig['ftp_server']);
$conn->ftp_login($repConfig['ftp_username'], $repConfig['ftp_password']);
$conn->ftp_pasv(true);
$conn->ftp_chdir($repConfig['ftp_basedir']);

/* Go through commits, execute the changes */
for ($i = 0; $i < count($payload['commits']); $i++) {
	
	/* Common variables */
	$commit = $payload['commits'][$i];
	$baseURL = "https://raw.githubusercontent.com/" . $repConfig['repo'] . "/" . $commit['id'] . "/";

	/* Print some info about the commit */
	echo "\nCommit: \"" . $commit['message'] . "\"\n";
	echo "ID: " . $commit['id'] . "\n";
	echo "Base URL: " . $baseURL . "\n";

	/* Add new files */
	foreach ($commit['added'] as $add) {
		if ($conn->ftp_size($add) > 0) {
			echo "Will not add " . $add . " because the file exists.\n";
		} else {
			verifyDir($conn, $add);
			$conn->ftp_put($add, $baseURL . $add, FTP_BINARY);
			echo "Added " . $add . "\n";
		}
	}

	/* Remove files */
	foreach ($commit['removed'] as $del) {
		$conn->ftp_delete($del);
		echo "Deleted " . $del . "\n";
		verifyDirDeletion($conn, $del);
	}
	
	/* Update files */
	foreach ($commit['modified'] as $mod) {
		verifyDir($conn, $add);
		$conn->ftp_put($mod, $baseURL . $mod, FTP_BINARY);
		echo "Modified " . $mod . "\n";
	}
}

$conn->ftp_close();
?>
