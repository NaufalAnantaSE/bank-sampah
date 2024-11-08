<?php
session_start();
require 'config/connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: page.php?mod=home");
    exit();
}

// // Periksa apakah pengguna adalah pengelola
// if ($_SESSION['user']['role'] !== 'warung_mitra') {
//     // Jika bukan pengelola, redirect ke halaman unauthorized
//     header("Location: page.php?mod=unaut2");
//     exit();
// }

$id_warung = $_SESSION['user']['id'];

// Menampilkan nama warung mitra
$query_nama = "SELECT nama_warung FROM warung_mitra WHERE id = '$id_warung'";
$result_nama = mysqli_query($conn, $query_nama);
$nama = mysqli_fetch_assoc($result_nama)['nama_warung'];

// Menampilkan saldo warung mitra
$query_saldo = "SELECT saldo FROM warung_mitra WHERE id = '$id_warung'";
$result_saldo = mysqli_query($conn, $query_saldo);
$saldo = mysqli_fetch_assoc($result_saldo)['saldo'];

// Ambil transaksi pending
$query_pending = "SELECT t.*, r.nama AS nama_pembayar 
                  FROM transaksi t 
                  JOIN rumah_tangga r ON t.id_rumah_tangga = r.id 
                  WHERE t.id_warung_mitra = '$id_warung' AND t.status = 'pending'";
$result_pending = mysqli_query($conn, $query_pending);

// ambil transaksi gagal
$query_gagal = "SELECT t.*, wm.nama_warung, r.nama AS nama_pembayar 
                  FROM transaksi t 
                  JOIN warung_mitra wm ON t.id_warung_mitra = wm.id 
                  JOIN rumah_tangga r ON t.id_rumah_tangga = r.id 
                  WHERE t.id_warung_mitra = '$id_warung' AND t.status = 'gagal'
                  ORDER BY t.tanggal DESC";
$result_gagal = mysqli_query($conn, $query_gagal);

// Ambil transaksi penarikan
$query_penarikan = "SELECT tp.*, wm.nama_warung, wm.saldo AS saldo_warung
                    FROM transaksi_pencairan tp
                    JOIN warung_mitra wm ON tp.id_warung_mitra = wm.id
                    WHERE tp.id_warung_mitra = '$id_warung' 
                    AND (tp.status = 'gagal' OR tp.status = 'pending')";
$result_penarikan = mysqli_query($conn, $query_penarikan);


// Ambil riwayat pembayaran
$query_riwayat = "SELECT t.*, wm.nama_warung, r.nama AS nama_pembayar 
                  FROM transaksi t 
                  JOIN warung_mitra wm ON t.id_warung_mitra = wm.id 
                  JOIN rumah_tangga r ON t.id_rumah_tangga = r.id 
                  WHERE t.id_warung_mitra = '$id_warung' 
                  ORDER BY t.tanggal DESC";
$result_riwayat = mysqli_query($conn, $query_riwayat);

// Ambil riwayat penarikan
$query_riwayat_penarikan = "SELECT rp.*, wm.nama_warung
                           FROM riwayat_penarikan rp
                           JOIN warung_mitra wm ON rp.id_warung_mitra = wm.id
                           WHERE rp.id_warung_mitra = '$id_warung'
                           ORDER BY rp.tanggal DESC";
$result_riwayat_penarikan = mysqli_query($conn, $query_riwayat_penarikan);

// Cek apakah tombol hapus riwayat pembayaran yang gagal diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_riwayat_id'])) {
    $hapus_riwayat_id = $_POST['hapus_riwayat_id'];
    $query_hapus_riwayat = "DELETE FROM transaksi WHERE id = '$hapus_riwayat_id' AND status = 'gagal'";
    mysqli_query($conn, $query_hapus_riwayat);

    // Redirect atau tampilkan pesan sukses
    echo "<script>alert('Riwayat pembayaran gagal berhasil dihapus.'); window.location.href='page.php?mod=warung';</script>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaksi_id = $_POST['transaksi_id'];

    // Ambil data transaksi
    $query_transaksi = "SELECT * FROM transaksi WHERE id = '$transaksi_id'";
    $result_transaksi = mysqli_query($conn, $query_transaksi);
    $transaksi = mysqli_fetch_assoc($result_transaksi);

    if ($transaksi) {
        $id_rumah_tangga = $transaksi['id_rumah_tangga'];
        $id_warung_mitra = $transaksi['id_warung_mitra'];
        $jumlah_pembayaran = $transaksi['jumlah_pembayaran'];

        // Mulai transaksi database
        mysqli_begin_transaction($conn);

        try {
            // Ambil saldo rumah tangga
            $query_rumah_tangga = "SELECT saldo FROM rumah_tangga WHERE id = '$id_rumah_tangga'";
            $result_rumah_tangga = mysqli_query($conn, $query_rumah_tangga);
            $rumah_tangga = mysqli_fetch_assoc($result_rumah_tangga);

            // Perbarui saldo rumah tangga
            $saldo_baru_rumah_tangga = $rumah_tangga['saldo'] - $jumlah_pembayaran;
            $update_rumah_tangga = "UPDATE rumah_tangga SET saldo = '$saldo_baru_rumah_tangga' WHERE id = '$id_rumah_tangga'";
            mysqli_query($conn, $update_rumah_tangga);

            // Ambil saldo warung mitra
            $query_warung_mitra = "SELECT saldo FROM warung_mitra WHERE id = '$id_warung_mitra'";
            $result_warung_mitra = mysqli_query($conn, $query_warung_mitra);
            $warung_mitra = mysqli_fetch_assoc($result_warung_mitra);

            // Perbarui saldo warung mitra
            $saldo_baru_warung_mitra = $warung_mitra['saldo'] + $jumlah_pembayaran;
            $update_warung_mitra = "UPDATE warung_mitra SET saldo = '$saldo_baru_warung_mitra' WHERE id = '$id_warung_mitra'";
            mysqli_query($conn, $update_warung_mitra);

            // Ubah status transaksi menjadi selesai
            $update_transaksi = "UPDATE transaksi SET status = 'selesai' WHERE id = '$transaksi_id'";
            mysqli_query($conn, $update_transaksi);

            // Commit transaksi
            mysqli_commit($conn);
            echo "Transaksi berhasil diproses.";
            echo "<script>alert('Transaksi berhasil diproses.'); window.location.href='page.php?mod=warung';</script>";
            ;
        } catch (Exception $e) {
            // Rollback jika terjadi kesalahan
            mysqli_rollback($conn);
            echo "Terjadi kesalahan: " . $e->getMessage();
            echo "<script>alert('Terjadi kesalahan: '); window.location.href='page.php?mod=warung';</script>";
            $e->getMessage();
        }
    } else {
        echo "Transaksi tidak ditemukan.";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gagal_id'])) {

        // Proses untuk menghapus transaksi
        $gagal_id = $_POST['gagal_id'];
        $query_gagal = "UPDATE transaksi SET status = 'gagal' WHERE id = '$gagal_id'";
        mysqli_query($conn, $query_gagal);


        // Redirect atau tampilkan pesan sukses
        echo "<script>alert('Transaksi Telah Gagal'); window.location.href='page.php?mod=warung';</script>";

    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {

        // Proses untuk menghapus transaksi
        $hapus_id = $_POST['hapus_id'];
        $query_hapus = "DELETE FROM transaksi WHERE id = '$hapus_id'";
        mysqli_query($conn, $query_hapus);
        // Redirect atau tampilkan pesan sukses
        echo "<script>alert('Transaksi berhasil dihapus.'); window.location.href='page.php?mod=warung';</script>";

    }

}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warung Mitra Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            margin-top: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            font-weight: bold;
            color: #222;
            margin-bottom: 20px;
        }

        .data-item {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
        }

        .data-label {
            font-weight: bold;
            flex: 1;
            min-width: 150px;
            color: #555;
        }

        .data-value {
            flex: 2;
            color: #333;
        }

        .btn-warning {
            background-color: #ffbb33;
            font-weight: 600;
        }

        .btn-warning:hover {
            background-color: #ff8800;
        }

        footer {
            margin-top: 50px;
            text-align: center;
            font-size: 0.9rem;
            color: #aaa;
        }

        .btn-spacing {
            margin-right: 5px;
            /* Atur jarak sesuai kebutuhan */
        }

        .btn-group form:last-child .btn-spacing {
            margin-right: 0;
            /* Menghilangkan margin pada tombol terakhir */
        }
    </style>
</head>

<body>
    <?php include 'assets/components/headerwarung.php'; ?>
        <!-- Riwayat Pembayaran Section -->
        <div class="container mt-5">
        <h2>Riwayat Pembayaran</h2>
        <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
            <?php while ($riwayat = mysqli_fetch_assoc($result_riwayat)): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="data-item">
                            <span class="data-label">Nama Pembayar:</span>
                            <span class="data-value"><?= $riwayat['nama_pembayar'] ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Status:</span>
                            <span class="data-value"><?= $riwayat['status'] ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Nama Warung:</span>
                            <span class="data-value"><?= $riwayat['nama_warung'] ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Jumlah Pembayaran (Rp):</span>
                            <span class="data-value"><?= number_format($riwayat['jumlah_pembayaran'], 2, ',', '.') ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Keterangan:</span>
                            <span class="data-value"><?= $riwayat['keterangan'] ?></span>
                        </div>
                        <div class="data-item">
                            <span class="data-label">Tanggal:</span>
                            <span class="data-value"><?= date('d-m-Y', strtotime($riwayat['tanggal'])) ?></span>
                        </div>

                        <?php if ($riwayat['status'] === 'gagal'): ?>
                            <!-- Tombol Hapus untuk Pembayaran Gagal -->
                            <div class="btn-group mt-2 justify-content-center d-flex align-items-center">
                                <form method="POST">
                                    <input type="hidden" name="hapus_riwayat_id" value="<?= $riwayat['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm btn-spacing"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus riwayat pembayaran ini?')">Hapus</button>
                                </form>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Tidak ada riwayat pembayaran.</p>
        <?php endif; ?>
    </div>


    <footer>
        <p>&copy; 2024 Warung Mitra. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://kit.fontawesome.com/0b79c15f2d.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx"
        crossorigin="anonymous"></script>
</body>

</html>