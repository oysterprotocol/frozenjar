<?php
include("fj_common.php");

if (php_sapi_name()!="cli") exit;

$fj_load_select = fj_load_select();

//FREEZE JAR MARKS BLOCKS STAGE 3
$fj_time = time();
$fj_result = fj_db("SELECT `fj_jar` FROM `fj_front`.`fj_jar` WHERE `fj_state` > 2 AND `fj_state` < ".$fj_time, array(4000, "fj_front"));
if ($fj_result->num_rows>0) {
	while ($fj_row = $fj_result->fetch_assoc()) foreach ($fj_load_select as $fj_server) fj_db("UPDATE `fj_load`.`fj_safe` SET `fj_stage` = 3 WHERE `fj_jar` = '".$fj_row['fj_jar']."' AND `fj_stage` = 2", $fj_server);
	fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_state` = 0 WHERE `fj_jar` = '".$fj_row['fj_jar']."'");
}
//FREEZE JAR MARKS BLOCKS STAGE 3

//CHECK SAFE PROGRESS
$fj_result = fj_db("SELECT `fj_jar` FROM `fj_front`.`fj_jar` WHERE (`fj_state` = 0 AND `fj_safe` != 0) OR (`fj_state` = 1 AND `fj_safe` != `fj_max`)", array(4000, "fj_front"));
if ($fj_result->num_rows>0) {
	while ($fj_row = $fj_result->fetch_assoc()) {
		$fj_count = 0;
		foreach ($fj_load_select as $fj_server) {
			$fj_result_sub = fj_db("SELECT COUNT(`fj_jar`) as `fj_count` FROM `fj_load`.`fj_safe` WHERE `fj_jar` = '".$fj_row['fj_jar']."' AND (`fj_stage` = 2 OR `fj_stage` = 3)");
			if ($fj_result_sub->num_rows>0) $fj_count += intval($fj_result_sub->fetch_assoc()['fj_count']);
		}
		fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_safe` = ".$fj_count." WHERE `fj_jar` = '".$fj_row['fj_jar']."'", array(4000, "fj_front"));
	}
}
//CHECK SAFE PROGRESS

//AUTO FREEZE SCHEDULE
fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_state` = ".($fj_time+604800)." WHERE `fj_state` = 1 AND `fj_safe` = `fj_max`");
//AUTO FREEZE SCHEDULE