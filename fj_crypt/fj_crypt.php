<?php
include("fj_common.php");

if (php_sapi_name()!="cli"&&$_SERVER['HTTP_USER_AGENT']!="85878b79d5667d7552c6f18625e54657") exit;//add additional auth that if not cli must come from fj_front ip address

require "/home/tree/vendor/autoload.php";
use Blocktrail\SDK\BlocktrailSDK;

$fj_pay_min = 1000000;
$fj_coin_buffer = 50000;
$fj_coin_earn = "0.2";
$fj_coin_profit = "18SboDKkfABqnDSV9t9NqJjXXYZDLHSS5c";
$fj_client = new BlocktrailSDK("8fe19c7b3d92974957d07a9c2bc54afa381dd8ac", "b5c6f3cec3e21588f1fcab04a3d1b4f9c901b2c1", "BTC", true /* testnet */);
$fj_wallet = $fj_client->initWallet("fj_payout", "12345678");

if (php_sapi_name()!="cli") {
	$fj_address = $fj_wallet->getNewAddress();
	$fj_client->subscribeAddressTransactions("fj_hook", $fj_address, 6);
	echo $fj_address;
}
else {
	$fj_time = time();
	$fj_load_select = fj_load_select();
	foreach($fj_load_select as $fj_server) {
		$fj_result = fj_db("SELECT `fj_payout`, `fj_last`, `fj_count_calc` FROM `fj_load`.`fj_payout` WHERE `fj_last` > ".($fj_time-2592000), $fj_server);
		if ($fj_result->num_rows>0) {
			fj_db("UPDATE `fj_load`.`fj_payout` SET `fj_count_calc` = '0' WHERE `fj_last` > ".($fj_time-2592000), $fj_server);
			while ($fj_row = $fj_result->fetch_assoc()) {
				$fj_result_sub = fj_db("SELECT `fj_payout` FROM `fj_crypt`.`fj_payout` WHERE `fj_payout` = '".$fj_row['fj_payout']."'");
				if ($fj_result_sub->num_rows==0) fj_db("INSERT INTO `fj_crypt`.`fj_payout` (`fj_payout`, `fj_start`, `fj_count_calc`, `fj_count_total`, `fj_coin_last`, `fj_coin_amount`, `fj_ban_time`, `fj_ban_reason`) VALUES('".$fj_row['fj_payout']."', ".$fj_time.", ".$fj_row['fj_count_calc'].", ".$fj_row['fj_count_calc'].", 0, 0, 0, 0)");
				else fj_db("UPDATE `fj_crypt`.`fj_payout` SET `fj_count_calc` = `fj_count_calc` + ".$fj_row['fj_count_calc'].", `fj_count_total` = `fj_count_total` + ".$fj_row['fj_count_calc']);
			}
		}
	}

	$fj_coin_bank = intval(fj_db("SELECT `fj_value` FROM `fj_crypt`.`fj_dynamic` WHERE `fj_index` = 'fj_coin_bank'")['fj_value']);
	$fj_coin_send = $fj_wallet->getMaxSpendable()['max']-$fj_coin_bank;
	$fj_coin_send_earn = intval(bcmul(strval($fj_coin_send), $fj_coin_earn));
	$fj_coin_send_node = $fj_coin_send - $fj_coin_send_earn;
	if ($fj_coin_send_node>=$fj_pay_min) {
		$fj_calc_total = fj_db("SELECT SUM(`fj_count_calc`) as `fj_calc_total` FROM `fj_crypt`.`fj_payout` WHERE `fj_ban_time` = 0")->fetch_assoc()['fj_calc_total'];
		$fj_pay_ratio = ($fj_coin_send_node-$fj_coin_buffer)/$fj_calc_total;

		$fj_count_min = round($fj_pay_min/$fj_pay_ratio);
		$fj_result = fj_db("SELECT `fj_payout`, `fj_count_calc` FROM `fj_crypt`.`fj_payout` WHERE `fj_count_calc` >= ".$fj_count_min." AND `fj_coin_last` < ".($fj_time-86400)." AND `fj_ban_time` = 0 ORDER BY `fj_count_calc` DESC LIMIT 40");
		$fj_payout_array = array();
		if ($fj_result->num_rows>0) while ($fj_row = $fj_result->fetch_assoc()) $fj_payout_array[$fj_row['fj_payout']] = $fj_row['fj_count_calc'];
		$fj_pay_eligible = intval(bcdiv(strval(array_sum($fj_payout_array)), strval($fj_calc_total), 5));
		if ($fj_pay_eligible>0.2) {
			$fj_coin_bank += intval(bcmul(strval($fj_coin_send_earn), strval($fj_pay_eligible)));
			if ($fj_coin_bank>=$fj_pay_min) $fj_coin_push[$fj_coin_profit] = $fj_coin_profit;
			else fj_db("UPDATE `fj_crypt`.`fj_dynamic` SET `fj_value` = ".$fj_coin_bank." WHERE `fj_index` = 'fj_coin_bank'");
			$fj_coin_sql = "";
			$fj_coin_push = array();
			foreach ($fj_payout_array as $fj_payout => $fj_count_calc) {
				$fj_amount = round($fj_count_calc*$fj_pay_ratio, 3);
				$fj_coin_push[$fj_payout] = $fj_amount;
				$fj_coin_sql .= "UPDATE `fj_crypt`.`fj_payout` SET `fj_count_calc` = 0, `fj_coin_last` = ".$fj_time.", `fj_coin_amount` = `fj_coin_amount` + ".$fj_amount." WHERE `fj_payout` = '".$fj_payout."';";
				$fj_coin_sql .= "INSERT INTO `fj_crypt`.`fj_coin` (`fj_payout`, `fj_amount`, `fj_share`, `fj_time`) VALUES('".$fj_payout."', ".$fj_amount.", ".floor(($fj_count_calc/$fj_calc_total)*100).", ".$fj_time.");";
			}
			$fj_coin_fail = false;
			try {
				$fj_wallet->pay($fj_coin_push);
			}
			catch (\Exception $e) {
				$fj_coin_fail = true;
			}
			if ($fj_coin_fail===false) {
				fj_db("UPDATE `fj_crypt`.`fj_dynamic` SET `fj_value` = '0' WHERE `fj_index` = 'fj_coin_bank'");
				mysqli_multi_query($fj_conn, $fj_coin_sql);
			}
		}
	}
}