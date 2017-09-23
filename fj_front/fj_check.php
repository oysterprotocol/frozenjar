<?php
include("fj_common.php");
if (!isset($_POST['fj_jar'])||!is_hash($_POST['fj_jar'], 32)) exit;

$fj_time = time();
$fj_result = fj_db("SELECT `fj_exp`, `fj_size`, `fj_address` FROM `fj_front`.`fj_jar` WHERE `fj_jar` = '".$_POST['fj_jar']."'");
if ($fj_result->num_rows==0) echo json_encode(array(0));
else {
	$fj_row = $fj_result->fetch_assoc();
	echo json_encode(array(1, $fj_row['fj_exp'], $fj_row['fj_size'], $fj_row['fj_address']));
}