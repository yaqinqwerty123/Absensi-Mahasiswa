<?php
session_start();
include "koneksi.php";

date_default_timezone_set('Asia/Jakarta');

// ===============================
// KONFIGURASI LOKASI ABSENSI
// ===============================
$CENTER_LAT = -7.157197932656336;
$CENTER_LNG = 113.49101646077567;
$MAX_RADIUS = 100; // meter

function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
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

    $nim   = isset($_POST['nim']) ? trim($_POST['nim']) : '';
    $pass  = isset($_POST['password']) ? md5(trim($_POST['password'])) : '';
    $shift = isset($_POST['shift']) ? $_POST['shift'] : '';
    $ket   = isset($_POST['jenis_absen']) ? $_POST['jenis_absen'] : '';
    $lat   = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $lng   = isset($_POST['longitude']) ? $_POST['longitude'] : '';


    // VALIDASI INPUT
    if ($nim=='' || $pass=='' || $shift=='' || $ket=='') {
        $error = "Semua field wajib diisi";
    }

    // VALIDASI LOKASI
    if ($error=='' && ($lat=='' || $lng=='')) {
        $error = "Lokasi tidak terbaca, aktifkan GPS";
    }

    if ($error=='') {
        $jarak = hitungJarak($lat, $lng, $CENTER_LAT, $CENTER_LNG);
        if ($jarak > $MAX_RADIUS) {
            $error = "Anda berada di luar lokasi absensi";
        }
    }

    // VALIDASI LOGIN
    if ($error=='') {
        $qMhs = mysql_query("
            SELECT * FROM mahasiswa
            WHERE nim='".mysql_real_escape_string($nim)."'
            AND password_mhs='$pass'
            AND status='1'
            LIMIT 1
        ");

        if (mysql_num_rows($qMhs)==0) {
            $error = "NIM atau password salah";
        } else {
            $m = mysql_fetch_assoc($qMhs);
            $id_mhs = $m['id_mhs'];
        }
    }

    // ===============================
    // ABSEN DATANG
    // ===============================
    if ($error=='' && $ket=='H') {

        $cek = mysql_query("
            SELECT id FROM absensi
            WHERE id_mhs='$id_mhs'
            AND DATE(tanggal)='$today'
            AND keterangan='H'
            AND id_shift='$shift'
        ");

        if (mysql_num_rows($cek)>0) {
            $error = "Anda sudah absen datang hari ini";
        } else {
            mysql_query("
                INSERT INTO absensi (id_mhs,id_shift,tanggal,keterangan)
                VALUES ('$id_mhs','$shift','$nowDatetime','H')
            ");
            $sukses = "Absen datang berhasil";
        }
    }

    // ===============================
    // ABSEN PULANG
    // ===============================
    if ($error=='' && $ket=='Pulang') {

        $cekDatang = mysql_query("
            SELECT id FROM absensi
            WHERE id_mhs='$id_mhs'
            AND keterangan='H'
            AND id_shift='$shift'
            AND tanggal >= DATE_SUB('$nowDatetime', INTERVAL 6 HOUR)
        ");


        if (mysql_num_rows($cekDatang)==0) {
            $error = "Anda belum absen datang";
        } else {

            $cekPulang = mysql_query("
                SELECT id FROM absensi
                WHERE id_mhs='$id_mhs'
                AND DATE(tanggal)='$today'
                AND keterangan='Pulang'
                AND id_shift='$shift'
            ");

            if (mysql_num_rows($cekPulang)>0) {
                $error = "Anda sudah absen pulang hari ini";
            } else {
                mysql_query("
                    INSERT INTO absensi (id_mhs,id_shift,tanggal,keterangan)
                    VALUES ('$id_mhs','$shift','$nowDatetime','Pulang')
                ");
                $sukses = "Absen pulang berhasil";
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
    <input type="hidden" name="absen" value="1">


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

    <button type="submit" id="btnAbsen">ABSEN SEKARANG</button>

</form>

</div>

<script>
const form = document.querySelector('form');
const btn  = document.getElementById('btnAbsen');
let isSubmitting = false;

form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (isSubmitting) return;

    if (!navigator.geolocation) {
        Swal.fire('Error', 'Browser tidak mendukung GPS', 'error');
        return;
    }

    isSubmitting = true;
    btn.disabled = true;

    Swal.fire({
        title: 'Mengambil lokasi...',
        text: 'Pastikan GPS aktif',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            document.getElementById('latitude').value  = pos.coords.latitude;
            document.getElementById('longitude').value = pos.coords.longitude;

            Swal.close();
            form.submit(); // submit ASLI setelah GPS dapet
        },
        function (err) {
            isSubmitting = false;
            btn.disabled = false;
            Swal.close();

            let msg = 'Gagal mengambil lokasi';
            if (err.code === 1) msg = 'Izin lokasi ditolak';
            if (err.code === 2) msg = 'Lokasi tidak tersedia';
            if (err.code === 3) msg = 'GPS timeout, coba lagi';

            Swal.fire('Error', msg, 'error');
        },
        {
            enableHighAccuracy: false,
            timeout: 15000,
            maximumAge: 60000
        }
    );
});
</script>




<?php if ($error!='') { ?>
<script>
Swal.fire({ icon:'error', text:'<?= addslashes($error) ?>' });
</script>
<?php } ?>

<?php if ($sukses!='') { ?>
<script>
Swal.fire({ icon:'success', text:'<?= addslashes($sukses) ?>' });
</script>
<?php } ?>


</body>
</html>
