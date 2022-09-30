<?php
require_once('includes/config.php');
// json response

//https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=GOOG&apikey=$key
//https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=GOOG&apikey=1878AU9E6PCOED1E
// https://www.alphavantage.co/query?function=TIME_SERIES_INTRADAY&symbol=IBM&interval=5min&apikey=demo
// "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&apikey=$key&outputsize=full"
// https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&outputsize=full&apikey=demo

$curl = curl_init();
$symbol = "AAPL";
// $symbol = "INFY.NS";

curl_setopt_array($curl, array(
	CURLOPT_URL => "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&apikey=$key&outputsize=full",
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
	// echo $response;
}

// convert the response to php array or object
// Fungsi dibawah ini untuk mengambil data dari API, mengubah data Json menjadi Array/Objek
// Mengambil data dari API demo Trade IBM dengan ketentuan Time Series Daily, waktu dan data trade


$array = json_decode($response, true);
echo "<br><pre>";
print_r($array);
print_r($array['Time Series (Daily)'] ['2021-12-13']['2. high']);
echo "</pre>";


// // we should get all these dates, so that we can get the days information with these dates
// Mengambil data tanggal / date dari API secara keseluruhan data dan tanggalnya.
$dates = array_keys($array['Time Series (Daily)']);

echo "<pre>";
print_r($dates);
echo "</pre>";

// Fungsi untuk membuat array dari data api json berdasarkan tanggal dan value dari trade perusahaan IBM
// Yang didalamnya terdapat high,low,open,close,volume
// 2021-12-13 Array ( [1. open] => 123.7600 [2. high] => 124.3554 [3. low] => 120.7900 [4. close] => 122.5800 [5. volume] => 6847468 ) | Contoh
// // remove 0.0000 from the output - 2019-10-27, 2005-07-28 Menghapus data 0.0000 jika ada di data json O,H,L,C
// // we can insert the values into database with this foreach loop
// // we should remove the zero values from the output
// Melakukan fungsi logika melakukan pengecekan apabila ada nilai ohcl yang 0

foreach ($dates as $date) {
	if($array['Time Series (Daily)'][$date]['1. open'] != '0.0000'){
		echo $date . " ";
		print_r($array['Time Series (Daily)'][$date]);
		echo "<br>";
	}
}






?>