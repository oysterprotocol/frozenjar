<?php
include("fj_common.php");
if (!isset($_POST['fj_push'])) exit;

function fj_coin_validate($address){
	$decoded = decodeBase58($address);

	$d1 = hash("sha256", substr($decoded,0,21), true);
	$d2 = hash("sha256", $d1, true);

	if (substr_compare($decoded, $d2, 21, 4)) return false;
	return true;
}
function decodeBase58($input) {
	$alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

	$out = array_fill(0, 25, 0);
	for ($i=0;$i<strlen($input);$i++){
		if (($p=strpos($alphabet, $input[$i]))===false){
			throw new \Exception("invalid character found");
		}
		$c = $p;
		for ($j = 25; $j--; ) {
			$c += (int)(58 * $out[$j]);
			$out[$j] = (int)($c % 256);
			$c /= 256;
			$c = (int)$c;
		}
		if ($c != 0){
			throw new \Exception("address too long");
		}
	}

	$result = "";
	foreach($out as $val){
		$result .= chr($val);
	}

	return $result;
}

$fj_time = time();
$fj_send = json_decode($_POST['fj_push'], true);
if (count($fj_send)!=4||($fj_send[0]!=1&&$fj_send[0]!=2)||!is_hash($fj_send[2], 40)||$fj_send[2]!=sha1($fj_send[3])) exit;
$fj_result = fj_db("SELECT `fj_jar`, `fj_exp`, `fj_last`, `fj_call` FROM `fj_load`.`fj_report` WHERE `fj_scan` = '".$fj_send[1]."'");
if ($fj_result->num_rows!=1) exit;
$fj_row = $fj_result->fetch_assoc();
$fj_rate = microtime(true) - $fj_row['fj_last'];
if (($fj_send[0]==1&&$fj_row['fj_call']==1)||($fj_send[0]==2&&$fj_rate<fj_db("SELECT `fj_value` FROM `fj_load`.`fj_dynamic` WHERE `fj_index` = 'fj_rate_lower'")->fetch_assoc()['fj_value'])) {
	$fj_load_select = fj_load_select();
	$fj_exist_array = array();
	foreach ($fj_load_select as $fj_load => $fj_server) {
		if (fj_db("SELECT `fj_scan` FROM `fj_load`.`fj_safe` WHERE `fj_scan` = '".$fj_send[2]."'", $fj_server)->num_rows>0) $fj_exist_array[$fj_load] = true;
	}
	if (count($fj_exist_array)==2) exit;
	$fj_jar = substr($fj_send[3], 0, 32);
	if ($fj_jar!=$fj_row['fj_jar']) exit;
	$fj_file = substr($fj_send[3], 32, 36);
	$fj_block = substr($fj_send[3], 36, 42);
	$fj_max = substr($fj_send[3], 42, 48);
	$fj_sig = substr($fj_send[3], 48, 58);
	foreach ($fj_load_select as $fj_load => $fj_server) {
		if (count($fj_exist_array)==2) break;
		if (isset($fj_exist_array[$fj_load])) continue;
		fj_db("INSERT IGNORE INTO `fj_load`.`fj_safe` (`fj_scan`, `fj_jar`, `fj_sig`, `fj_file`, fj_block, `fj_max`, `fj_exp`, `fj_rate`, `fj_last`, `fj_start`, `fj_streak`, `fj_stage`, `fj_content`) VALUES('".$fj_send[2]."', '".$fj_jar."', '".$fj_sig."', '".$fj_file."', '".$fj_block."', '".$fj_max."', '".$fj_row['fj_exp']."', '".$fj_rate."', '".$fj_time."', '".$fj_time."', 0, ".($fj_send[0]*2).", '".substr($fj_send[3], 58)."')", $fj_server);
		$fj_exist_array[$fj_load] = true;
	}
	if (count($fj_exist_array)>=2&&$fj_row['fj_call']==1) foreach ($fj_load_select as $fj_server) fj_db("UPDATE `fj_load`.`fj_report` SET `fj_call` = 0 WHERE `fj_scan` = '".$fj_send[2]."'", $fj_server);
	if ($fj_send[1]!=-1&&fj_coin_validate($fj_send[1])==true) {
		$fj_result = fj_db("SELECT `fj_payout` FROM `fj_load`.`fj_payout` WHERE `fj_payout` = '".$fj_send[1]."'");
		if ($fj_result->num_rows==0) fj_db("INSERT INTO `fj_load`.`fj_payout` (`fj_payout`, `fj_last`, `fj_start`, `fj_count_calc`, `fj_count_total`) VALUES('".$fj_send[1]."', ".$fj_time.", ".$fj_time.", 1, 1)");
		else fj_db("UPDATE `fj_load`.`fj_payout` SET `fj_last` = ".$fj_time.", `fj_count_calc` = `fj_count_calc` + 1, `fj_count_total` = `fj_count_total` + 1 WHERE `fj_payout` = '".$fj_send[1]."'");
	}
}