<?php
require_once('includes/config.php');
require_once('includes/connect.php');
// Karena kita menggunakan/ memberikan sesi kepada pengguna untuk menajalkannya menggunakan session_start
// token berfungsi sebagai keamanan, jika pengguna tidak memiliki token yang sama dengan sistem, dan sistem mengidikasi adanya token yang digunakan padahal sudah kadaluwarsa maka pengguna tidak akan bisa melakukan perubahan atau menambahkan data pada sistem
session_start();
// CSRF Token Protection
if(isset($_POST) & !empty($_POST)){
    // Print_r($_POST) berguna untuk menampilkan data sementara apakah data berhasil tampil atau tidak (Pengecekan)
    //print_r($_POST);
    // PHP Form Validations
    // Fungsi untuk melakukan proses pengecekan apabila simbol dari saham sudah pernah digunakan atau sudah ada di dalam database
    if(empty($_POST['stock'])){ $errors[] = "Stock Field is Required"; }else{
        // chekc the symbol is unique with db query (select)
        $sql = "SELECT * FROM stocks WHERE symbol=?";
        $result = $db->prepare($sql);
        $res = $result->execute(array($_POST['stock'])) or die(print_r($result->errorInfo(), true));
        $count = $result->rowCount();
        if($count == 1){
            $errors[] = "Stock Symbol already exists in database";
        }
    }

    //3. Validate the CSRF Token (CSRF Token Validation & CSRF Token Time Validation)
    // Melakukan validasi Untuk menyamakan token yang dimiliki pengguna dengan token yang ada pada sistem saat ini
    // Jika ada csrf_token maka dilakukan pengecekan apakah csrf_token user sama dengan sistem jika berbeda maka error.
    // CSRF Token Validation
    if(isset($_POST['csrf_token'])){
        if($_POST['csrf_token'] === $_SESSION['csrf_token']){
        }else{
            $errors[] = "Problem with CSRF Token Verification";
        }
    }else{
        $errors[] = "Problem with CSRF Token Validation";
    }

    // CSRF Token Time Validation
    // Jika pengguna tidak melakukan apapun selama 1 x 24 jam maka session akan di lepas dan berganti expired
    // Jika session dari pengguna atau sesi pengguna dalam mengakses halaman add stock melebihi max_time maka tidak bisa menginputkan apapun harus reload
    $max_time = 60*60*24; // time in seconds
    if(isset($_SESSION['csrf_token_time'])){
        // compare the time with maxtime
        $token_time = $_SESSION['csrf_token_time'];
        if(($token_time + $max_time) >= time()){ // nothing here
        }else{
            // display error message and unset the CSRF Tokens
            $errors[] = "CSRF Token Expired";
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
        }
    }else{
        // unset the CSRF Tokens
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
    }

    // Proses pengambil data dari api dan disimpan ke dalam database Mysql diambil dari get-name.php sebagai file pengelola api 
    if(empty($errors)){
        $curl = curl_init();
        $symbol = $_POST['stock'];
        // $symbol = $_POST['stock'].".".$_POST['exchange'];
        // $symbol = "AAPL";

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
$companyname = $name['data'][0]['name'];
// echo $array['data'][0]['symbol'];



        // Proses memasukan data dari API ke dalam database
        // Insert SQL query to insert into stocks table
        $sql = "INSERT INTO stocks (symbol, name, exchange) VALUES (:symbol, :name, :exchange)";
        $result = $db->prepare($sql);
        $values = array(':symbol'   => $_POST['stock'],
                        ':name'     => $companyname,
                        ':exchange' => $_POST['exchange']
                        );
        $res = $result->execute($values) or die(print_r($result->errorInfo(), true));
        // Jika Proses memasukan data stocks berhasil ke dalam database maka akan otomatis menjalankan perintan memasukan data harian,bulanan,tahunan ke dalam database dengan fungsi dibawah ini 
        // Fatal error diakibatkan karena durasi php memperoses data tidak cukup sehingga harus diberikan batas maksimum lebih dari yang sekarang ditetapkan. Php tidak bisa mengambil banyak data dalam jangka waktu yang ditetapkan sekarang (Note repaired)
        if($res){
            // get the last insert id and get the daily values of this stock and insert to stock_daily_values table data yang digunakan menggunakan data berdasarkan API 
            $stockid = $db->lastInsertID();
            // geting the response from the API
            $curl = curl_init();
            // $symbol = "AAPL";
            // $symbol = "INFY.NS";
            // Dipilih compact untuk mengambil tahun ini saja, jika full maka seluruh data tahun akan tersimpan
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
                // Add respon berfungsi untuk melihat hasil eksekusi api
                echo $response;
                // Memasukan data Dari API ke dalam database
                // After that loop through the daily values and insert those values in daily_values table
                // here we can get the weekly & monthly response and insert into respective tables, will do it in a seperate PHP page
                // json_decode untuk melepas array dari json sehingga bisa digunakan dan ditangap menggunakan array yang dibuat sendiri
                $data = json_decode($response, true);
                $dates = array_keys($data['Time Series (Daily)']);
                foreach ($dates as $date) {
                     if(isset($data['Time Series (Daily)'][$date]) & !empty($data['Time Series (Daily)'][$date])){
                if($data['Time Series (Daily)'][$date]['1. open'] != '0.0000'){
                    // Insert into stock_daily_values tabel
                    $dailysql = "INSERT INTO stock_daily_values (stockid, price_open, price_high, price_low, price_close, volume, trade_date) VALUES (:stockid, :price_open, :price_high, :price_low, :price_close, :volume, :trade_date)";
                        $dailyresult = $db->prepare($dailysql);
                          $values = array(':stockid'      => $stockid,
                                            ':price_open'   => $data['Time Series (Daily)'][$date]['1. open'],
                                            ':price_high'   => $data['Time Series (Daily)'][$date]['2. high'],
                                            ':price_low'    => $data['Time Series (Daily)'][$date]['3. low'],
                                            ':price_close'  => $data['Time Series (Daily)'][$date]['4. close'],
                                            ':volume'       => $data['Time Series (Daily)'][$date]['5. volume'],
                                            ':trade_date'   => $date
                                            );   
                    $dailyres = $dailyresult->execute($values) or die(print_r($dailyresult->errorInfo(), true));
                     echo $date . " Added<br>";
    }


            }
        }
         $messages[] = "Stock Added Successfully";
    }
                

             // geting the response from the API
            // $curl = curl_init();

            // curl_setopt_array($curl, array(
            //     CURLOPT_URL => "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$symbol&apikey=$key&outputsize=full",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_FOLLOWLOCATION => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 90,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "GET"
            // ));

            // $response = curl_exec($curl);
            // $err = curl_error($curl);

            // curl_close($curl);

            // if($err){
            //     echo "cURL Error :" . $err;
            // }else{
            //     //echo $response;
            //     // After that loop through the daily values and insert those values in daily_values table
            //     // here we can get the weekly & monthly response and insert into respective tables, will do it in a seperate PHP page
            //     $data = json_decode($response, true);
            //     $dates = array_keys($data['Time Series (Daily)']);
            //     foreach ($dates as $date) {
            //         if(isset($data['Time Series (Daily)'][$date]) & !empty($data['Time Series (Daily)'][$date])){
            //             if($data['Time Series (Daily)'][$date]['1. open'] != '0.0000'){

            //                 // Insert into stock_daily_values table
            //                 $dailysql = "INSERT INTO stock_daily_values (stockid, price_open, price_high, price_low, price_close, volume, trade_date) VALUES (:stockid, :price_open, :price_high, :price_low, :price_close, :volume, :trade_date)";
            //                 $dailyresult = $db->prepare($dailysql);
            //                 $values = array(':stockid'      => $stockid,
            //                                 ':price_open'   => $data['Time Series (Daily)'][$date]['1. open'],
            //                                 ':price_high'   => $data['Time Series (Daily)'][$date]['2. high'],
            //                                 ':price_low'    => $data['Time Series (Daily)'][$date]['3. low'],
            //                                 ':price_close'  => $data['Time Series (Daily)'][$date]['4. close'],
            //                                 ':volume'       => $data['Time Series (Daily)'][$date]['5. volume'],
            //                                 ':trade_date'   => $date
            //                                 );

            //                 $dailyres = $dailyresult->execute($values) or die(print_r($dailyresult->errorInfo(), true));
            //                 //echo $date . " Added<br>";

            //             }
            //         }
            //     }
            //     $messages[] = "Stock Added Successfully";
            // }
        }
    }
}

//1. Create CSRF Token
// Membuat CSRF Token, sehingga token hanya bisa di akses dari halaman ini saja
// Dengan menggunakan Hash md5
$token = md5(uniqid(rand(), TRUE));
// Pemberian session artinya masing masing pengguna nantinya akan mendapatkan token yang berbeda-beda
// Sesuai dengan token yang mereka dapatkan dari sesi mereka menggunakan sistem
$_SESSION['csrf_token'] = $token;
$_SESSION['csrf_token_time'] = time();

//2. Add CSRF Token to Form

include('includes/header.php');
include('includes/navigation.php');
?>
<div id="page-wrapper" style="min-height: 345px;">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Add Stock</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Create a New Stock Here...
                </div>
                <div class="panel-body">
                    <?php
                        if(!empty($errors)){
                            echo "<div class='alert alert-danger'>";
                            foreach ($errors as $error) {
                                echo "<span class='glyphicon glyphicon-remove'></span>&nbsp;" . $error ."<br>";
                            }
                            echo "</div>";
                        }
                    ?>
                    <?php
                        if(!empty($messages)){
                            echo "<div class='alert alert-success'>";
                            foreach ($messages as $message) {
                                echo "<span class='glyphicon glyphicon-ok'></span>&nbsp;" . $message ."<br>";
                            }
                            echo "</div>";
                        }
                    ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <form role="form" method="post">
                                <div class="col-lg-6">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <div class="form-group">
                                        <label>Stock Scrip</label>
                                        <input class="form-control" name="stock" placeholder="Enter Stock Scrip Name">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label>Stock Exchange</label>
                                        <select name="exchange" class="form-control">
                                            <option value="XNAS">XNAS</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="submit" class="btn btn-primary" value="Submit" />
                            </form>
                        </div>
                        <!-- /.col-lg-6 (nested) -->   
                    <!-- /.row (nested) -->
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
</div>
<?php
include('includes/footer.php');
?>