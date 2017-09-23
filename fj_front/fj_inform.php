<?php
include("fj_common.php");

//if (!isset($_GET['sc_pass'])||$_GET['sc_pass']!="1718d47c1cd25414a1846b5564328f71") exit;

$sc_json = file_get_contents("php://input");
$_POST = json_decode($sc_json, true);

if ($_POST['network']!="tBTC"||$_POST['event_type']!="address-transactions"||count($_POST['addresses'])==0) exit;

$sc_time = time();
$fj_address = null;
$fj_amount = null;
foreach ($_POST['addresses'] as $fj_address => $fj_amount) {}

$fj_price = fj_db("SELECT `fj_value` FROM `fj_front`.`fj_dynamic` WHERE `fj_index` = 'fj_jar_price'")['fj_value'];
$fj_worth = ceil($fj_amount/$fj_price);

$fj_result = fj_db("SELECT `fj_event` FROM `fj_front`.`fj_event` WHERE `fj_event` = '".$_POST['data']['hash']."'");
if ($fj_result->num_rows==0) fj_db("INSERT INTO `fj_front`.`fj_event` (`fj_event`, `fj_address`, `fj_amount`, `fj_worth`, `fj_price`, `fj_confirm`, `fj_start`, `fj_last`) VALUES('".$_POST['data']['hash']."', '".$fj_address."', ".$fj_amount.", ".$fj_worth.", ".$fj_price.", ".$_POST['data']['confirmations'].", ".$fj_time.", ".$fj_time.")");
else fj_db("UPDATE `fj_front`.`fj_event` SET `fj_confirm` = ".$_POST['data']['confirmations'].", `fj_last` = '".$fj_time."' WHERE `fj_event` = '".$_POST['data']['hash']."'");

$fj_result = fj_db("SELECT `fj_jar` FROM `fj_front`.`fj_address` WHERE `fj_address` = '".$fj_address."'");
if ($fj_result->num_rows==1) {
	$fj_jar = $fj_result->fetch_assoc()['fj_jar'];
	if ($_POST['data']['confirmations']==0) {
		fj_db("UPDATE `fj_front`.`fj_address` SET `fj_amount` = `fj_amount` + ".$fj_amount.", `fj_worth` = `fj_worth` + ".$fj_worth.", `fj_last` = ".$fj_time." WHERE `fj_address` = '".$fj_address."'");
		$fj_result = fj_db("SELECT `fj_size`, `fj_worth`, `fj_start` FROM `fj_front`.`fj_jar` WHERE `fj_jar` = '".$fj_jar."'");
		if ($fj_result->num_rows==1) {
			$fj_row = $fj_result->fetch_assoc();
			$fj_worth_sum = $fj_row['fj_worth']+$fj_worth;
			$fj_address_new = fj_address_get();
			fj_db("UPDATE `fj_front`.`fj_jar` SET `fj_exp` = ".($fj_row['fj_start']+ceil($fj_worth_sum/$fj_row['fj_size'])).", `fj_worth` = ".$fj_worth_sum.", `fj_last` = ".$fj_time.", `fj_address` = '".$fj_address_new."' WHERE `fj_jar` = '".$fj_jar."'");
		}
	}
}

//HAVE AN ABNORMAL LOG TO CHECK FOR CENTRALIZED ABNORMAL BEHAVIOR, SUCH AS ROWS BEING 0 WHILST CONFIRM BEING GREATER THAN ZERO