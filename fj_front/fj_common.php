<?php
//VAR
$fj_load_range = array(4001, 4002, 4003, 4004);
//VAR
$fj_conn = new mysqli("localhost", "blank", "blank", "fj_front");
$fj_conn_remote = array();

function fj_db($fj_sql, $fj_server = false) {
	global $fj_conn;
	global $fj_conn_remote;

	if ($fj_server===false) return $fj_conn->query($fj_sql);
	if (!isset($fj_conn_remote[$fj_server[0]])||!$fj_conn_remote[$fj_server[0]]->ping()) $fj_conn_remote[$fj_server[0]] = new mysqli("127.0.0.1", "blank", "blank", $fj_server[1], $fj_server[0]);
	return $fj_conn_remote[$fj_server[0]]->query($fj_sql);
}

function fj_connect($fj_url, $fj_content = false) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $fj_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	if ($fj_content!==false) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fj_content);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$result = curl_exec($ch);
	return $result;
}
function fj_address_get() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://10.130.46.15/fj_crypt.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "85878b79d5667d7552c6f18625e54657");
	return curl_exec($ch);
}
function is_hash($fj_str, $fj_length) {
	return (bool) preg_match("/^[0-9a-f]{".$fj_length."}$/i", $fj_str);
}
function fj_rand_secure($min, $max) {
	$range = $max - $min;
	if ($range < 0) return $min; // not so random...
	$log = log($range, 2);
	$bytes = (int) ($log / 8) + 1; // length in bytes
	$bits = (int) $log + 1; // length in bits
	$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
	do {
		$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
		$rnd = $rnd & $filter; // discard irrelevant bits
	} while ($rnd >= $range);
	return $min + $rnd;
}
function fj_rand($fj_length = 32){
	$token = "";
	$codeAlphabet = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	for($i=0;$i<$fj_length;$i++){
		$token .= $codeAlphabet[fj_rand_secure(0,strlen($codeAlphabet))];
	}
	return $token;
}
function fj_return($fj_return_code) {
	echo $fj_return_code;
	exit;
}
function fj_load_select() {
	global $fj_load_range;

	$fj_load_select = array();
	foreach ($fj_load_range as $fj_load) {
		$fj_server = array($fj_load, "fj_load");
		$fj_result_check = fj_db("SELECT `fj_value` FROM `fj_load`.`fj_dynamic` WHERE `fj_index` = 'fj_ping'", $fj_server);
		if ($fj_result_check->num_rows==1&&intval($fj_result_check->fetch_assoc()['fj_value'])==1) $fj_load_select[$fj_load] = $fj_server;
	}
	return $fj_load_select;
}