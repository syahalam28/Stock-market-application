<?php
require_once('includes/connect.php');
include('includes/header.php');
include('includes/navigation.php');
?>
<div id="page-wrapper" style="min-height: 345px;">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">View Stocks</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    View All the Stocks 
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Stock</th>
                                    <th>FV</th>
                                    <th>Days</th>
                                    <th>Start Price</th>
                                    <th>Current Price</th>
                                    <th>ATL</th>
                                    <th>ATH</th>
                                    <th>exchange</th> 
                                    <th>Operations</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $sql = "SELECT * FROM stocks";
                                $result = $db->prepare($sql);
                                $result->execute() or die(print_r($result->errorInfo(), true));
                                $stocks = $result->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($stocks as $stock) {


                                    // // We can get the number of days by counting the number of rows in db
                                    // Exampel
                                    // Mengambil data harian 
                                    $daysql = "SELECT * FROM stock_daily_values WHERE stockid=?";
                                    $dayresult = $db->prepare($daysql);
                                    // Jika data ada maka ambil id jika gagal maka tampilkan error
                                    $dayres = $dayresult->execute(array($stock['id'])) or die(print_r($dayresult->errorInfo(), true));
                                    $daycount = $dayresult->rowCount();
                                    $stocklh = $dayresult->fetchAll(PDO::FETCH_ASSOC);

                                    // We should get the frist & last record for start & current Price
                                    // Dari Tanggal terakhir ke terbaru
                                    $sql = "SELECT * FROM stock_daily_values WHERE stockid=? ORDER BY trade_date ASC LIMIT 1";
                                    $result = $db->prepare($sql);
                                    // Jika data ada maka ambil id jika gagal maka tampilkan error
                                    $result->execute(array($stock['id'])) or die(print_r($result->errorInfo(), true));
                                    $stockstartvals = $result->fetch(PDO::FETCH_ASSOC);
                                    // Untuk pengujian mengambil data/ lihat data
                                    // print_r($stockstartvals)

                                    // Tanggal Terbaru ke terakhir/ saat ini current 
                                     $sql = "SELECT * FROM stock_daily_values WHERE stockid=? ORDER BY trade_date DESC LIMIT 1";
                                    $result = $db->prepare($sql);
                                    // Jika data ada maka ambil id jika gagal maka tampilkan error
                                    $result->execute(array($stock['id'])) or die(print_r($result->errorInfo(), true));
                                    $stockcurrentvals = $result->fetch(PDO::FETCH_ASSOC);
                                    // Untuk pengujian mengambil data/ lihat data
                                    // print_r($stockvals)

                                    // Calculating All time low & Hight from full record set
                                        $pricelh = array_column($stocklh, "price_open");
                                        // Menampilkan nilai dari $pricelh, data yang ditampilkan merupaan price open dari setiap stock
                                        // echo "<pre>";
                                        // print_r($pricelh);
                                        // echo "</pre>";

                                        // Mengambil data nilai terendah/tertinggi berdasarkan stock/saham
                                        $stocklow = $stocklh[array_search(min($pricelh), $pricelh)];
                                        $stockhigh = $stocklh[array_search(max($pricelh), $pricelh)];
                                        // echo "<pre>";
                                        // print_r($stockhigh);
                                        // echo "</pre>";


                                    // Akhir Exampel




                                    // $sql = "SELECT * FROM stock_cache_values WHERE stockid=?";
                                    // $result = $db->prepare($sql);
                                    // $res = $result->execute(array($stock['id'])) or die(print_r($result->errorInfo(), true));
                                    // $stockvals = $result->fetch(PDO::FETCH_ASSOC);
                            ?>
                                <tr>
                                    <!-- Example -->
                                        <td><?php echo $stock['id']; ?></td>
                                        <td><a href="view-stock.php?scrip=<?php echo $stock['symbol']; ?>"><?php echo $stock['symbol']; ?></a><br><small><?php echo $stock['name']; ?></small>
                                         </td>
                                        <td></td>
                                        <td><?php echo $daycount; ?></td>
                                        <td>
                                            <!-- Fungsi round terkait dengan pembulatan angka nilai harga -->
                                            <?php echo round($stockstartvals['price_open'],2); ?>
                                            <br><small><?php echo $stockstartvals['trade_date']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo round($stockcurrentvals['price_open'],2); ?>
                                            <br><small><?php echo $stockcurrentvals['trade_date']; ?></small>
                                        </td>
                                        <td>
                                             <?php echo round($stocklow['price_open'],2); ?>
                                            <br><small><?php echo $stocklow['trade_date']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo round($stockhigh['price_open'],2); ?>
                                            <br><small><?php echo $stockhigh['trade_date']; ?></small>
                                        </td>
                                        <td><?php echo $stock['exchange']; ?></td>
                                        <td>
                                            <a href="chart.php?scrip=<?php echo $stock['symbol']; ?>">View Chart</a>
                                        </td>
                                        <td></td>
                                        <td></td>
                                                 <!-- Akhir Exaample -->



                                  <!--   <td><?php echo $stock['id']; ?></td>
                                    <td><a href="view-stock.php?scrip=<?php echo $stock['symbol']; ?>"><?php echo $stock['symbol']; ?></a><br><small><?php echo $stock['name']; ?></small>
                                    </td>
                                    <td>Otto</td>
                                    <td><?php echo $stockvals['days']; ?></td>
                                    <td><?php echo round($stockvals['startprice'],2); ?>
                                        <br><small><?php echo $stockvals['startdate']; ?></small>
                                    </td>
                                    <td><?php echo round($stockvals['currentprice'],2); ?>
                                        <br><small><?php echo $stockvals['currentdate']; ?></small>
                                    </td>
                                    <td><?php echo round($stockvals['atl_price'],2); ?>
                                        <br><small><?php echo $stockvals['atl_date']; ?></small>
                                    </td>
                                    <td><?php echo round($stockvals['ath_price'],2); ?>
                                        <br><small><?php echo $stockvals['ath_date']; ?></small>
                                    </td>
                                    <td><?php echo $stock['exchange']; ?></td>
                                    <td><a href="chart.php?scrip=<?php echo $stock['symbol']; ?>">View Chart</td> -->
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.table-responsive -->
                </div>
                <!-- /.panel-body -->
            </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
</div>

<?php
include('includes/footer.php');
?>