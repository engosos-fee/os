<?php
$number = '01025027999';
$password = 'Medo@@123';

function checkGetToken($number, $password)
{
	$token = '';
	$url1 = "https://mobile.vodafone.com.eg/auth/realms/vf-realm/protocol/openid-connect/token";
	$headers1 = [
	'Accept' => 'application/json, text/plain, */*',
	'Connection' => 'keep-alive',
	'x-agent-operatingsystem' => 'V12.0.17.0.QJQMIXM',
	'clientId' => 'AnaVodafoneAndroid',
	'x-agent-device' => 'lime',
	'x-agent-version' => '2022.1.2.3',
	'x-agent-build' => '500',
	'Content-Type' => 'application/x-www-form-urlencoded',
	'Content-Length' => '145',
	'Host' => 'mobile.vodafone.com.eg',
	'Accept-Encoding' => 'gzip',
	'User-Agent' => 'okhttp/4.9.1',
];

$data1 = [
	'username' => $number,
	'password' => $password,
	'grant_type' => 'password',
	'client_secret' => 'a2ec6fff-0b7f-4aa4-a733-96ceae5c84c3',
	'client_id' => 'my-vodafone-app'
];


$ch = curl_init($url1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data1));

$response1 = curl_exec($ch);
curl_close($ch);

$responseData1 = json_decode($response1, true);

if (isset($responseData1['access_token'])) {
		$token = $responseData1['access_token'];
		//echo "DONE LOGIN";
		return $token;
	} else {
		//echo " Error Login";
		return false;
	}
}
function orderProduct($number, $password) 
{
	$token = checkGetToken($number, $password);
	$url2 = "https://web.vodafone.com.eg/services/dxl/pim/product?relatedParty.id=01025027999&place.@referredType=Local&@type=MIProfile";
	$headers2 = [
	"Accept: application/json",
	"Accept-Language: EN",
	"Authorization: Bearer $token",
	"Connection: keep-alive",
	"Content-Type: application/json",
	"Referer: https://web.vodafone.com.eg/spa/flexManagement/internet",
	"Sec-Fetch-Dest: empty",
	"Sec-Fetch-Mode: cors",
	"Sec-Fetch-Site: same-origin",
	"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36",
	"clientId: WebsiteConsumer",
	"msisdn: $number",
	'sec-ch-ua: "Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"',
	'sec-ch-ua-mobile: ?0',
	'sec-ch-ua-platform: "Windows"'
	];
	
	 $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url2);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers2);
	
	$response = curl_exec($ch);
	curl_close($ch);

	$data = json_decode($response, true);
	
	if (is_array($data) && isset($data['productList'])) {
		$product_list = $data['productList'];
	} elseif (is_array($data)) {
		$product_list = $data;
	} else {
		return "البيانات غير متوقعة";
	}

	$target_id = "MI_BASIC_SUPER_20";
	$enc_product_id = null;
	
	
	foreach ($product_list as $product) {
		if (is_array($product) && isset($product['id']) && $product['id'] === $target_id) {
			$enc_product_id = $product['productOffering']['encProductId'] ?? null;
			break;
		}
	}
	
	
	
	
	
	$url = 'https://web.vodafone.com.eg/services/dxl/pom/productOrder';
	$auth = "Bearer " . $token;

	$headers = [
		"Accept: application/json",
		"Accept-Language: EN",
		"Authorization: $auth",
		"Content-Type: application/json",
		"clientId: WebsiteConsumer",
		"msisdn: $number"
	];

	$json_data = json_encode([
		"channel" => ["name" => "MobileApp"],
		"orderItem" => [[
			"action" => "add",
			"product" => [
				"characteristic" => [
					["name" => "ExecutionType", "value" => "Sync"],
					["name" => "LangId", "value" => "en"],
					["name" => "MigrationType", "value" => "Repurchase"],
					["name" => "OneStepMigrationFlag", "value" => "Y"],
					["name" => "DropAddons", "value" => "True"]
				],
				"relatedParty" => [[
					"id" => $number,
					"name" => "MSISDN",
					"role" => "Subscriber"
				]],
				"id" => "MI_BASIC_SUPER_20",
				"@type" => "MI",
				"encProductId" => $enc_product_id
			]
		]],
		"@type" => "MIProfile"
	]);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

	$response = curl_exec($ch);
	var_dump($response);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	echo "HTTP Code: " . $http_code;
	curl_close($ch);

	$data = json_decode($response, true);
	
	if ($http_code != 200) {
		echo "HTTP Error: $http_code";
		return;
	}

	if (isset($data["code"]) && $data["code"] == "2252" && isset($data["reason"]) && $data["reason"] == "Insufficient balance") {
		echo "Done Add 1 GB";
	} else {
		echo "Request Failed! Response: " . json_encode($data);
	}
}


if (checkGetToken($number, $password) != false)
{
	orderProduct($number, $password);
}
?>