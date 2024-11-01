<?php
session_start();
require 'config/connect.php';

// Cek apakah pengelola sudah login
// if (!isset($_SESSION['user'])) {
//     header("Location: page.php?mod=home");
//     exit();
// }

// // Periksa apakah pengguna adalah pengelola
// if ($_SESSION['user']['role'] !== 'pengelola') {
//     // Jika bukan pengelola, redirect ke halaman unauthorized
//     header("Location: page.php?mod=unaut2");
//     exit();
// }

$id_pengelola = $_SESSION['user']['id'];

// Periksa apakah ada ID sampah yang diberikan
if (!isset($_GET['id'])) {
    header("Location: page.php?mod=pengelola");
    exit();
}

$id_sampah = $_GET['id'];

// Ambil data sampah berdasarkan ID untuk ditampilkan dalam form
$query_sampah = "SELECT s.*, js.harga_per_kg FROM sampah s
                 JOIN jenis_sampah js ON s.id_jenis_sampah = js.id
                 WHERE s.id = '$id_sampah'";
$result_sampah = mysqli_query($conn, $query_sampah);

// Jika tidak ada data sampah ditemukan, kembali ke halaman pengelola
if (mysqli_num_rows($result_sampah) == 0) {
    header("Location: page.php?mod=pengelola");
    exit();
}

$sampah = mysqli_fetch_assoc($result_sampah);

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $berat = $_POST['berat'];

    // Ambil harga per kg berdasarkan jenis sampah yang sudah ada
    $harga_per_kg = $sampah['harga_per_kg'];

    // Hitung total harga
    $total_harga = $berat * $harga_per_kg;

    // Update data sampah
    $query_update = "UPDATE sampah SET 
                        berat = '$berat',
                        total_harga = '$total_harga'
                    WHERE id = '$id_sampah'";
    if (mysqli_query($conn, $query_update)) {
        header("Location: page.php?mod=pengelola");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Ambil data jenis sampah untuk dropdown
$query_jenis_sampah = "SELECT * FROM jenis_sampah";
$result_jenis_sampah = mysqli_query($conn, $query_jenis_sampah);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hitung Sampah</title>
    <!-- Link CSS Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <?php include 'assets/components/headerpeng.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center">Hitung Sampah</h1>

        <form method="POST">
            <div class="form-group">
                <label for="jenis_sampah">Jenis Sampah</label>
                <select name="jenis_sampah" id="jenis_sampah" class="form-control" disabled>
                    <?php while ($jenis = mysqli_fetch_assoc($result_jenis_sampah)): ?>
                        <option value="<?= $jenis['id'] ?>" <?= ($jenis['id'] == $sampah['id_jenis_sampah']) ? 'selected' : '' ?>>
                            <?= $jenis['nama_jenis'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="berat">Berat (kg)</label>
                <input type="number" step="0.01" name="berat" id="berat" value="<?= $sampah['berat'] ?>"
                    class="form-control" required>
            </div>

            <div class="form-group">
                <label for="harga_per_kg">Harga per Kg (Rp)</label>
                <input type="number" step="0.01" name="harga_per_kg" id="harga_per_kg"
                    value="<?= $sampah['harga_per_kg'] ?>" class="form-control" readonly>
            </div>

            <!-- <div class="form-group">
                <label for="total_harga">Total Harga (Rp)</label>
                <input type="number" step="0.01" name="total_harga" id="total_harga" value="<?= $sampah['total_harga'] ?>" class="form-control" readonly>
            </div> -->

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="page.php?mod=pengelola" class="btn btn-secondary">Batal</a>
        </form>
    </div>

    <script>
        document.getElementById('berat').addEventListener('input', function () {
            const berat = parseFloat(this.value) || 0;
            const hargaPerKg = parseFloat(document.getElementById('harga_per_kg').value) || 0;
            document.getElementById('total_harga').value = (berat * hargaPerKg).toFixed(2);
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>