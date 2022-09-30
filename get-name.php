<?php
require_once('includes/config.php');

$curl = curl_init();
$symbol = "AAPL";
// https://api.marketstack.com/v1/eod?access_key=1d68025e2a7c8825bac0acfa7a8fff59&symbols=IBM
// https://api.marketstack.com/api/v1/stock?symbol=$symbol&api_token=$token
// Mengambil data akhir hari (end of date)
// http://api.marketstack.com/v1/eod?access_key=1d68025e2a7c8825bac0acfa7a8fff59&symbols=$symbol
// Untuk mengambil titik akhir api dengan mencari informasi tentang suatu atau beberapa simbol ticker saham serta memperoleh data saham
// http://api.marketstack.com/v1/tickers?access_key=1d68025e2a7c8825bac0acfa7a8fff59&symbols=AAPL
// Tickers + eod
// http://api.marketstack.com/v1/tickers/AAPL/eod?access_key=1d68025e2a7c8825bac0acfa7a8fff59&symbols=AAPL
// http karena pakai api gratis




curl_setopt_array($curl, array(
	CURLOPT_URL => "http://api.marketstack.com/v1/tickers?access_key=$token&symbols=$symbol",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 90,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET"
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if($err){
	echo "cURL Error :" . $err;
}else{
	//echo $response;
}

// convert the response to php array or object
$name = json_decode($response, true);
echo "<br><pre>";
print_r($name);
// print_r($array);
echo "</pre>";
// echo $array->data[0]->name;
// Fungsi ini untuk mengambil data nama dari saham karena data ter encode dalam bentuk array([data][0][name])
// name adalah nama attribut yang ada pada data json
echo $name['data'][0]['name'];
// echo $array['data'][0]['symbol'];

// Mengambil exchange dari api (Urgen)







?>


