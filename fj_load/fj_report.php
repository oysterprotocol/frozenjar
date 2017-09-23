<?php
include("fj_common.php");
ignore_user_abort(true);
if (!isset($_POST['fj_push'])) exit;

function fj_block_fetch($fj_block_key) {
	global $fj_response;

	$fj_result = fj_db("SELECT `fj_scan`, `fj_jar`, `fj_file`, `fj_block`, `fj_max`, `fj_exp`, `fj_content` FROM `fj_safe` WHERE `fj_stage` >= 2 ORDER BY `fj_rate` DESC LIMIT 1");
	if ($fj_result->num_rows==1) {
		$fj_row = $fj_result->fetch_assoc();
		fj_db("UPDATE `fj_load`.`fj_safe` SET `fj_rate` = -1 WHERE `fj_scan` = '".$fj_row['fj_scan']."'");//if fj_last is too long, then update fj_rate to the upper limit x2
		$fj_response[0][0] = $fj_block_key;
		$fj_response[0][1] = $fj_row['fj_jar'].$fj_row['fj_file'].$fj_row['fj_block'].$fj_row['fj_max'].$fj_row['fj_exp'].$fj_row['fj_content'];
		$fj_response[0][2] = sha1($fj_response[0][1]);
	}
}
$fj_time = time();
$fj_response = array(array(0), array(0));
$fj_space = 0;
$fj_report = json_decode($_POST['fj_push'], true);
$fj_report_key = 0;
$fj_result = fj_db("SELECT `fj_index`, `fj_value` FROM `fj_dynamic` WHERE `fj_index` = 'fj_rate_lower' OR `fj_index` = 'fj_rate_upper'");
$fj_rate_array = array();
$fj_rate_calc = false;
while ($fj_row = $fj_result->fetch_assoc()) {
	if ($fj_row['fj_index']=="fj_rate_lower") $fj_rate_array['fj_rate_lower'] = $fj_row['fj_index'];
	else if ($fj_row['fj_index']=="fj_rate_upper") $fj_rate_array['fj_rate_upper'] = $fj_row['fj_index'];
}
foreach ($fj_report as $fj_report_key => $fj_report_value) {
	if ($fj_report_key==0) {
		$fj_space = $fj_report_value;
		continue;
	}
	if (strlen($fj_report_value)!=40) continue;
	$fj_result = fj_db("SELECT `fj_jar`, `fj_exp`, `fj_last`, `fj_call`  FROM `fj_load`.`fj_report` WHERE `fj_scan` = '".$fj_report_value."'");
	if ($fj_result->num_rows==0) {
		//integ code here
		/*
		 * perhaps the data should be submitted to fj_suspect. if there is enough geographical variety in the reports for this bit then should check if the jar exists. if it exists then recall the jar and check if the bit works in it via an internal hash.
		 */
		continue;
	}
	$fj_row = $fj_result->fetch_assoc();
	$fj_jar = $fj_row['fj_jar'];
	$fj_rate = microtime(true) - $fj_row['fj_last'];
	$fj_call = $fj_row['fj_call'];
	unset($fj_row);
	fj_db("UPDATE `fj_load`.`fj_report` SET `fj_last` = '".$fj_time."' WHERE `fj_scan` = '".$fj_report_value."'");
	fj_db("INSERT INTO `fj_load`.`fj_rate` (`fj_rate`) VALUES(".$fj_rate.")");
	$fj_rate = round($fj_rate);
	if ($fj_response[0][0]==0&&(($fj_row['fj_exp']+2592000)<$fj_time||$fj_rate>$fj_rate_array['fj_rate_upper'])) {
		if ($fj_rate>$fj_rate_array['fj_rate_upper']==true) $fj_rate_calc = true;
		fj_block_fetch($fj_report_key);
	}
	$fj_result = fj_db("SELECT `fj_streak` FROM `fj_load`.`fj_safe` WHERE `fj_scan` = '".$fj_report_value."'");
	if ($fj_result->num_rows==1) {
		$fj_streak = $fj_result->fetch_assoc()['fj_streak'];
		if ($fj_rate>43200) $fj_streak = 0;
		else $fj_streak += $fj_rate;
		if ($fj_streak>604800) fj_db("DELETE FROM `fj_load`.`fj_safe` WHERE `fj_scan` = '".$fj_report_value."' AND `fj_stage` >= 3");
		else fj_db("UPDATE `fj_load`.`fj_safe` SET `fj_rate` = ".$fj_rate.", `fj_streak` = ".$fj_streak." WHERE `fj_scan` = '".$fj_report_value."'");
	}
	else if ($fj_response[1][0]==0) {
		if ($fj_call==1) {
			$fj_response[1][0] = $fj_report_key;
			$fj_response[1][1] = 1;
		}
		else if ($fj_rate<$fj_rate_array['fj_rate_lower']) {
			$fj_rate_calc = true;
			$fj_response[1][0] = $fj_report_key;
			$fj_response[1][1] = 2;
		}
	}
}
if ($fj_response[0][0]==0&&$fj_space>$fj_report_key) fj_block_fetch($fj_report_key+1);
if ($fj_rate_calc==true) {
	$fj_result = fj_db("SELECT `fj_rate` FROM `fj_load`.`fj_rate` WHERE 1 ORDER BY `fj_rate` DESC LIMIT 10000");
	$fj_num_rows = $fj_result->num_rows;
	if ($fj_num_rows>1000) {
		fj_db("TRUNCATE TABLE `fj_load`.`fj_rate`");
		$fj_rate_lower_index = intval(floor($fj_num_rows*0.1));
		$fj_rate_upper_index = intval(floor($fj_num_rows*0.9));
		$fj_rate_array = array();
		while ($fj_row = $fj_result->fetch_assoc()) $fj_rate_array[] = $fj_row['fj_rate'];
		if (isset($fj_rate_array[$fj_rate_lower_index])) fj_db("UPDATE `fj_load`.`fj_dynamic` SET `fj_value` = ".round($fj_rate_array[$fj_rate_lower_index])." WHERE `fj_index` = 'fj_rate_lower'");
		if (isset($fj_rate_array[$fj_rate_upper_index])) fj_db("UPDATE `fj_load`.`fj_dynamic` SET `fj_value` = ".round($fj_rate_array[$fj_rate_upper_index])." WHERE `fj_index` = 'fj_rate_upper'");
		fj_db("UPDATE `fj_load`.`fj_dynamic` SET `fj_value` = ".round(array_sum($fj_rate_array)/count($fj_rate_array))." WHERE `fj_index` = 'fj_rate_avg'");
		unset($fj_rate_array);
	}
}
echo json_encode($fj_response);