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



// Ambil data sampah yang siap untuk di-pickup
$query_sampah = "SELECT s.*, r.nama, r.rw, r.alamat, r.kontak, js.nama_jenis AS jenis_sampah 
                 FROM sampah s 
                 JOIN rumah_tangga r ON s.id_rumah_tangga = r.id 
                 JOIN jenis_sampah js ON s.id_jenis_sampah = js.id
                 WHERE s.status = 'siap hitung'";
$result_sampah = mysqli_query($conn, $query_sampah);

$query_sampah .= " ORDER BY r.rw, r.nama"; // Urutkan berdasarkan RW dan nama rumah tangga
$result_sampah = mysqli_query($conn, $query_sampah);

// Ambil semua history sampah
$query_history = "SELECT s.*, r.nama, r.alamat, r.kontak, js.nama_jenis AS jenis_sampah 
                  FROM sampah s 
                  JOIN rumah_tangga r ON s.id_rumah_tangga = r.id 
                  JOIN jenis_sampah js ON s.id_jenis_sampah = js.id
                  WHERE s.status = 'selesai'
                  ORDER BY s.id DESC";
$result_history = mysqli_query($conn, $query_history);

// Ambil total harga per rumah tangga (baik untuk pickup maupun history)
$query_total_sampah_pickup = "SELECT r.nama, SUM(s.total_harga) AS total_harga
                              FROM sampah s
                              JOIN rumah_tangga r ON s.id_rumah_tangga = r.id
                              WHERE s.status = 'menunggu_pickup'
                              GROUP BY r.id";
$result_total_sampah_pickup = mysqli_query($conn, $query_total_sampah_pickup);
$totals_by_household = [];

$query_total_sampah_selesai = "SELECT r.nama, SUM(s.total_harga) AS total_harga
                               FROM sampah s
                               JOIN rumah_tangga r ON s.id_rumah_tangga = r.id
                               WHERE s.status = 'selesai'
                               GROUP BY r.id";
$result_total_sampah_selesai = mysqli_query($conn, $query_total_sampah_selesai);
$totals_by_household1 = [];

// Simpan total harga per rumah tangga dalam array
while ($row = mysqli_fetch_assoc($result_total_sampah_pickup)) {
    $totals_by_household[$row['nama']] = $row['total_harga'];
}

// Isi array dengan hasil query untuk sampah 'selesai'
while ($row = mysqli_fetch_assoc($result_total_sampah_selesai)) {
    $totals_by_household1[$row['nama']] = $row['total_harga'];
}




if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_sampah = $_POST['id_sampah'];
    $confirmed_by_pengelola = $_POST['confirmed_by_pengelola'];

    // Ambil data sampah berdasarkan ID untuk menghitung saldo
    $query_sampah_detail = "SELECT * FROM sampah WHERE id = '$id_sampah'";
    $result_sampah_detail = mysqli_query($conn, $query_sampah_detail);
    $sampah = mysqli_fetch_assoc($result_sampah_detail);

    // Update status sampah
    $query_update_pengelola = "UPDATE sampah SET confirmed_by_pengelola = 'diterima' WHERE id = '$id_sampah'";
    mysqli_query($conn, $query_update_pengelola);

    // Jika status diubah menjadi "selesai", tambahkan saldo rumah tangga
    // if ($status == 'selesai') {
    //     $id_rumah_tangga = $sampah['id_rumah_tangga'];
    //     $total_harga = $sampah['total_harga'];

    //     // Update saldo rumah tangga
    //     $query_update_saldo = "UPDATE rumah_tangga SET saldo = saldo + $total_harga WHERE id = '$id_rumah_tangga'";
    //     mysqli_query($conn, $query_update_saldo);
    // }

    header("Location: page.php?mod=pengelola");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengelola Sampah</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

    <?php include 'assets/components/headerpeng.php'; ?>
            <!-- Pencarian -->

    </div>
    <div class="container mt-5">
        <h1 class="text-center">Pengelola Sampah Desa Salem</h1>
        <!-- Bagian Sampah Siap Pickup -->
        <h4 class="mt-4">Sampah Siap Pick-Up</h4>

        <?php if (mysqli_num_rows($result_sampah) > 0): ?>
            <?php
            $current_rw = null; // Variabel untuk menyimpan RW saat ini
            $current_household = null; // Variabel untuk menyimpan rumah tangga saat ini
            $total_pickup = 0;
            while ($sampah = mysqli_fetch_assoc($result_sampah)):
                // Cek apakah RW saat ini berbeda dengan RW sebelumnya
                if ($current_rw !== $sampah['rw']): ?>
                    <?php if ($current_rw !== null): ?>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Total Harga (Rp):</strong></td>
                            <td><strong><?= number_format($total_pickup, 2, ',', '.') ?></strong></td>
                            <td></td>
                        </tr>
                        </tbody>
                        </table>
                    <?php endif; ?>
                    <h4 class="mt-4">RW: <?= $sampah['rw'] ?></h4> <!-- Tampilkan RW -->
                    <?php
                    $current_rw = $sampah['rw']; // Set RW saat ini
                    $current_household = null; // Reset rumah tangga saat RW baru dimulai
                    $total_pickup = 0; // Reset total pickup untuk RW baru
                endif;

                // Cek apakah rumah tangga saat ini berbeda dengan rumah tangga sebelumnya
                if ($current_household !== $sampah['nama']): ?>
                    <?php if ($current_household !== null): ?>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Total Harga (Rp):</strong></td>
                            <td><strong><?= number_format($total_pickup, 2, ',', '.') ?></strong></td>
                            <td></td>
                        </tr>
                        </tbody>
                        </table>
                    <?php endif;
                    $current_household = $sampah['nama']; // Set rumah tangga saat ini
                    $total_pickup = 0; // Reset total pickup untuk rumah tangga baru ?>

                    <h5 class="mt-4">Rumah Tangga: <?= $sampah['nama'] ?></h5>
                    <p>Alamat: <?= $sampah['alamat'] ?></p>
                    <p>RW: <?= $sampah['rw'] ?></p>
                    <p>Kontak: <?= $sampah['kontak'] ?></p>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jenis Sampah</th>
                                <th>Berat (kg)</th>
                                <th>Total Harga (Rp)</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php endif; ?>
                        <tr>
                            <td><?= $sampah['jenis_sampah'] ?></td>
                            <td><?= number_format($sampah['berat'], 2, ',', '.') ?></td>
                            <td><?= number_format($sampah['total_harga'], 2, ',', '.') ?></td>
                            <td><?= $sampah['status'] ?></td>
                            <td><?= $sampah['confirmed_by_pengelola'] ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id_sampah" value="<?= $sampah['id'] ?>">
                                    <button value="selesai" name="status" class="btn btn-success mt-2"
                                        <?= ($sampah['confirmed_by_pengelola'] == 'diterima') ? 'disabled' : '' ?>>Selesai</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id_sampah" value="<?= $sampah['id'] ?>">
                                    <button type="submit" name="action" value="hapus" class="btn btn-danger mt-2">Hapus</button>
                                </form>
                                <a href="page.php?mod=edit&id=<?= $sampah['id'] ?>" class="btn btn-warning mt-2">Hitung
                                    Sampah</a>
                            </td>
                        </tr>
                        <?php
                        $total_pickup += $sampah['total_harga']; // Hitung total pickup per rumah tangga
            endwhile; ?>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total Harga (Rp):</strong></td>
                        <td><strong><?= number_format($total_pickup, 2, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p>Tidak ada sampah yang siap untuk di-pickup.</p>
        <?php endif; ?>



    </div>
</body>

</html>