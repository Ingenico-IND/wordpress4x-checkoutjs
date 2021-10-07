<?php

require_once('worldline.php');

add_action('admin_menu', 'menupage');

function menupage()
{
	add_menu_page('Page Title', 'Worldline Offline Verification ', 'manage_options', 'worldline-offline', 'O_call_req');
}

function O_call_req()
{
	$paynimo_class  = new WC_worldline();
?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<title>Bootstrap Example</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
	</head>

	<body>
		<div class="container">
			<div class="row">
				<div class="text">
					<h2>Offline Verification</h2>
				</div>
				<form class="form-inline" method="POST">

					Merchant Ref No: <input type="text" name="token" placeholder="Merchant Ref No." required>
					Date: <input type="date" name="date" placeholder="dd-mm-YYYY" required>
					<input type="submit" class="btn btn-primary" name="submit" value="Submit" />
				</form>
			</div>
			<br>
			<br>
	</body>

	</html>
	<?php
	$merchantTxnRefNumber = null;
	$date = null;
	$merchant_code = $paynimo_class->worldline_merchant_code;
	if (isset($_POST["token"])) {
		$merchantTxnRefNumber = $_POST["token"];
	}

	if (isset($_POST["date"])) {
		$date  = $_POST["date"];
	}
	$currency = $paynimo_class->currency_type;
	if (isset($_POST["submit"])) {
		$request_array = array(
			"merchant" => array("identifier" => $merchant_code),
			"transaction" => array(
				"deviceIdentifier" => "S",
				"currency" => $currency,
				"identifier" => $merchantTxnRefNumber,
				"dateTime" => $date,
				"requestType" => "O"
			)
		);
		$refund_data = json_encode($request_array);
		$refund_url = "https://www.paynimo.com/api/paynimoV2.req";
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
		$identifier = $response_array->paymentMethod->paymentTransaction->identifier;
		$amount = $response_array->paymentMethod->paymentTransaction->amount;
		$errorMessage = $response_array->paymentMethod->paymentTransaction->errorMessage;
		$dateTime = $response_array->paymentMethod->paymentTransaction->dateTime;
		$merchantTransactionIdentifier = $response_array->merchantTransactionIdentifier;
	?>
		<div class="container">
			<div class="col-12 col-sm-6">
				<table class="table table-bordered">

					<tbody>
						<tr>
							<th>Status Code</th>
							<th><?php echo $status_code; ?></th>
						</tr>
						<tr>
							<th>Merchant Transaction Reference No</th>
							<th><?php echo $merchantTransactionIdentifier; ?></th>
						</tr>
						<tr>
							<th>TPSL Transaction ID</th>
							<th><?php echo $identifier; ?></th>
						</tr>
						<tr>
							<th>Amount</th>
							<th><?php echo $amount; ?></th>
						</tr>
						<tr>
							<th>Message</th>
							<th><?php echo $errorMessage; ?></th>
						</tr>
						<tr>
							<th>Status Message</th>

							<th><?php $message = $status_message == true ? $status_message :  "Not Found";
								echo $message;  ?></th>
						</tr>
						<tr>
							<th>Date Time</th>
							<th><?php echo $dateTime; ?></th>
						</tr>

					</tbody>
				</table>
			</div>
		</div>
<?php
	}
}
