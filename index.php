<?php
session_start();
include "koneksi.php";

date_default_timezone_set('Asia/Jakarta');

// ===============================
// KONFIGURASI LOKASI ABSENSI
// ===============================
$CENTER_LAT = -7.157197932656336;
$CENTER_LNG = 113.49101646077567;
$MAX_RADIUS = 30; // meter

function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meter

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

$nowDatetime = date('Y-m-d H:i:s');
$today       = date('Y-m-d');

$error  = '';
$sukses = '';

// ===============================
// DATA SHIFT
// ===============================
$shiftOpt = '<option value="">Pilih Shift</option>';
$qShift = mysql_query("SELECT * FROM shift ORDER BY id ASC");
while ($s = mysql_fetch_assoc($qShift)) {
    $shiftOpt .= '<option value="'.$s['id'].'">'.$s['nama_shift'].'</option>';
}


// ===============================
// PROSES LOGIN + ABSEN
// ===============================
if (isset($_POST['absen'])) {

    $nim   = mysql_real_escape_string(trim($_POST['nim']));
    $pass  = md5(trim($_POST['password']));
    $shift = mysql_real_escape_string($_POST['shift']);
    $ket   = mysql_real_escape_string($_POST['jenis_absen']);


    // ===============================
    // VALIDASI LOKASI (WAJIB PALING AWAL)
    // ===============================
    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : '';

    if ($lat == '' || $lng == '') {
        $error = "Lokasi tidak terdeteksi, aktifkan GPS";
    } else {
        $jarak = hitungJarak($lat, $lng, $CENTER_LAT, $CENTER_LNG);
        if ($jarak > $MAX_RADIUS) {
            $error = "Anda tidak sedang berada di lokasi absensi";
        }
    }

   
    if ($nim == '' || $pass == '' || $shift == '' || $ket == '') {
        $error = "Semua field wajib diisi";
    } else {

        $qMhs = mysql_query("
            SELECT * FROM mahasiswa
            WHERE nim='$nim'
            AND password_mhs='$pass'
            AND status='1'
            LIMIT 1
        ");

        if (mysql_num_rows($qMhs) == 0) {
            $error = "NIM atau Password salah";
        } else {
            // print_r('nim bener sama password bener');
            // exit;
            $m = mysql_fetch_assoc($qMhs);
            $id_mhs = $m['id_mhs'];

            // ===============================
            // ABSEN DATANG
            // ===============================
            if ($ket == 'H') {
                // print_r('hadir');
                //             exit;
                $cek = mysql_query("
                    SELECT id FROM absensi
                    WHERE id_mhs='$id_mhs'
                    AND DATE(tanggal)='$today'
                    AND keterangan='H'
                    AND id_shift='$shift'
                    LIMIT 1
                ");

                if (mysql_num_rows($cek) > 0) {
                    $error = "Anda sudah absen datang hari ini";
                } else {

                    mysql_query("
                        INSERT INTO absensi
                        (id_mhs, id_shift, tanggal, keterangan)
                        VALUES
                        ('$id_mhs','$shift','$nowDatetime','H')
                    ");

                    $sukses = "Absen datang berhasil";
                }

            }
            // ===============================
            // ABSEN PULANG
            // ===============================
            else {

                $cekDatang = mysql_query("
                    SELECT id FROM absensi
                    WHERE id_mhs='$id_mhs'
                    AND DATE(tanggal)='$today'
                    AND keterangan='H'
                    AND id_shift='$shift'
                    LIMIT 1
                ");

                if (mysql_num_rows($cekDatang) == 0) {
                    $error = "Anda belum absen datang";
                } else {

                    $cekPulang = mysql_query("
                        SELECT id FROM absensi
                        WHERE id_mhs='$id_mhs'
                        AND DATE(tanggal)='$today'
                        AND keterangan='Pulang'
                        AND id_shift='$shift'
                        LIMIT 1
                    ");

                    if (mysql_num_rows($cekPulang) > 0) {
                        $error = "Anda sudah absen pulang hari ini";
                    } else {

                        mysql_query("
                            INSERT INTO absensi
                            (id_mhs, id_shift, tanggal, keterangan)
                            VALUES
                            ('$id_mhs','$shift','$nowDatetime','Pulang')
                        ");

                        $sukses = "Absen pulang berhasil";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Absensi Mobile</title>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
* { box-sizing: border-box; }

body {
    margin:0;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg,#1e88e5,#42a5f5);
}

.card {
    background:#fff;
    width:100%;
    max-width:420px;
    padding:25px;
    border-radius:14px;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
    animation: fadeIn .5s ease;
}

@keyframes fadeIn {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:none; }
}

h2 {
    margin:0;
    text-align:center;
    color:#1e88e5;
}

.sub {
    text-align:center;
    font-size:13px;
    color:#666;
    margin-bottom:20px;
}

label {
    font-size:13px;
    color:#444;
}

input, select {
    width:100%;
    padding:12px;
    margin-top:6px;
    margin-bottom:14px;
    border-radius:8px;
    border:1px solid #ddd;
    font-size:15px;
}

input:focus, select:focus {
    outline:none;
    border-color:#1e88e5;
}

button {
    width:100%;
    padding:14px;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:bold;
    background:#1e88e5;
    color:#fff;
}

button:active {
    transform:scale(.98);
}
</style>
</head>
<body>

<div class="card">
    <h2>Absensi Mahasiswa</h2>
    <div class="sub">SIAKAD</div>

<form method="post">
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">

    <label>NIM</label>
    <input type="text" name="nim" placeholder="Masukkan NIM" required>

    <label>Password</label>
    <input type="password" name="password" placeholder="Password" required>

    <label>Jenis Absensi</label>
    <select name="jenis_absen" required>
        <option value="">Pilih Jenis Absensi</option>
        <option value="H">Absen Datang</option>
        <option value="Pulang">Absen Pulang</option>
    </select>

    <label>Shift</label>
    <select name="shift" required>
        <?php echo $shiftOpt; ?>
    </select>

    <button type="submit" name="absen">ABSEN SEKARANG</button>

</form>

</div>

<script>
const CENTER_LAT = -7.157197932656336;
const CENTER_LNG = 113.49101646077567;
const MAX_RADIUS = 0; // meter

function hitungJarak(lat1, lon1, lat2, lon2) {
    const R = 6371000; // meter
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

const btn = document.querySelector('button[name="absen"]');

btn.addEventListener('click', function(e) {
    e.preventDefault();

    if (!navigator.geolocation) {
        Swal.fire({
            icon: 'error',
            text: 'Browser tidak mendukung GPS'
        });
        return;
    }

    Swal.fire({
        title: 'Mengambil lokasi...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            const jarak = hitungJarak(
                lat, lng,
                CENTER_LAT, CENTER_LNG
            );

            if (jarak > MAX_RADIUS) {
                Swal.fire({
                    icon: 'error',
                    title: 'Di luar jangkauan',
                    text: 'Anda tidak berada di lokasi absensi'
                });
                return; // ❌ STOP DI SINI — TIDAK SUBMIT
            }

            // ✅ LOKASI VALID → BARU SUBMIT
            document.getElementById('latitude').value  = lat;
            document.getElementById('longitude').value = lng;

            Swal.close();
            btn.closest('form').submit();
        },
        function() {
            Swal.fire({
                icon: 'error',
                text: 'GPS wajib diaktifkan dan diizinkan'
            });
        },
        {
            enableHighAccuracy: true,
            timeout: 15000
        }
    );
});
</script>


<?php if (isset($_POST['absen']) && $error !== '') { ?>
<script>
Swal.fire({
    icon: 'error',
    text: '<?= addslashes($error); ?>'
});
</script>
<?php } ?>

<?php if (isset($_POST['absen']) && $sukses !== '') { ?>
<script>
Swal.fire({
    icon: 'success',
    text: '<?= addslashes($sukses); ?>'
});
</script>
<?php } ?>


</body>
</html>
