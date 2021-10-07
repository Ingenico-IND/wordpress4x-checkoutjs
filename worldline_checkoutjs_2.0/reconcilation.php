<?php
require_once('worldline.php');

add_action('admin_menu', 'menupage2');
function menupage2()
{
	add_menu_page('Page Title2', 'Reconcilation ', 'manage_options', 'reconcilation', 'recon_default');
}

function recon_default()
{
?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<title></title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
		<style>
			.border {
				display: inline-block;
				width: 70px;
				height: 70px;
				margin: 6px;
			}
		</style>
	</head>

	<body>
		<div class="container">
			<div class="row">
				<div class="text">
					<h2>Reconcilation</h2>
				</div>
				<form class="form-inline" method="POST">

					From Date: <input type="date" name="from_date" placeholder="dd-mm-YYYY" required>
					To Date: <input type="date" name="to_date" placeholder="dd-mm-YYYY" required>
					<input type="submit" class="btn btn-primary" name="submit" value="Submit" />
				</form>
			</div>
			<br>
			<br>
	</body>

	</html>
	<?php
	global $woocommerce;
	global $wpdb;
	$from_date = null;
	$to_date = null;
	if (isset($_POST["from_date"])) {
		$from_date = $_POST["from_date"];
	}
	if (isset($_POST["to_date"])) {
		$to_date  = $_POST["to_date"];
	}
	if (isset($_POST["submit"])) {
		$args = array(

			"status" => "wc-pending",
			"date_created" => $from_date . '...' . $to_date,
			"order" => "DESC",
			"payment_method" => "worldline"

		);

		$get_orders = wc_get_orders($args);

		$paynimo_class  = new WC_worldline();
		$merchant_code = $paynimo_class->worldline_merchant_code;
		$successFullOrdersIds = [];

		foreach ($get_orders as $order_array) {
			$order_id = $order_array->get_id();
			$currency = $order_array->get_currency();


			$woocommerce_object = new WC_Order($order_id);
			$date_input = $woocommerce_object->get_date_created()->format('d-m-Y');
			$id = $woocommerce_object->get_date_created()->format('d-m-Y');

			$table_name = $wpdb->prefix . 'worldlinedetails';

			$query = $wpdb->get_results("SELECT merchantid FROM " . $table_name . " WHERE orderid =" . $order_id);
			$merchantTxnRefNumber = $query[0]->merchantid;

			$request_array = array(
				"merchant" => array("identifier" => $merchant_code),
				"transaction" => array(
					"deviceIdentifier" => "S",
					"currency" => $currency,
					"identifier" => $merchantTxnRefNumber,
					"dateTime" => $date_input,
					"requestType" => "O"
				)
			);
			$refund_data = json_encode($request_array);
			$url = "https://www.paynimo.com/api/paynimoV2.req";
			$options = array(
				'http' => array(
					'method'  => 'POST',
					'content' => json_encode($request_array),
					'header' =>  "Content-Type: application/json\r\n" .
						"Accept: application/json\r\n"
				)
			);
			$context     = stream_context_create($options);
			$response_array = json_decode(file_get_contents($url, false, $context));

			$status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
			$status_message = $response_array->paymentMethod->paymentTransaction->statusMessage;
			$txn_id = $response_array->paymentMethod->paymentTransaction->identifier;
			if ($status_code == '0300') {
				$success_ids = $woocommerce_object->get_id();
				$woocommerce_object->set_transaction_id($txn_id);
				array_push($successFullOrdersIds, $success_ids);
				$woocommerce_object->update_status('processing');
				$woocommerce_object->save();
			} else if ($status_code == "0397" || $status_code == "0399" || $status_code == "0396" || $status_code == "0392") {
				$success_ids = $woocommerce_object->get_id();
				$woocommerce_object->set_transaction_id($txn_id);
				array_push($successFullOrdersIds, $success_ids);
				$woocommerce_object->update_status('cancelled');
				$woocommerce_object->save();
			} else {
				null;
			}
		}
		if ($successFullOrdersIds) {
			$message = "Updated Order Status for Order ID:  " . implode(", ", $successFullOrdersIds);
		} else {
			$message = "Updated Order Status for Order ID: None";
		}
	?>
		<div class="container">
			<p><mark><?php echo $message; ?></mark></p><span class="border"></span>
		</div>
<?php
	}
}
