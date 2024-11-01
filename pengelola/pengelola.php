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

// Ambil total harga per rumah tangga
$query_total_sampah_pickup = "SELECT r.nama, SUM(s.total_harga) AS total_harga
                              FROM sampah s
                              JOIN rumah_tangga r ON s.id_rumah_tangga = r.id
                              WHERE s.status = 'menunggu_pickup'
                              GROUP BY r.id";
$result_total_sampah_pickup = mysqli_query($conn, $query_total_sampah_pickup);

// Query untuk total harga sampah selesai
$query_total_sampah_selesai = "SELECT r.nama, SUM(s.total_harga) AS total_harga
                               FROM sampah s
                               JOIN rumah_tangga r ON s.id_rumah_tangga = r.id
                               WHERE s.status = 'selesai'
                               GROUP BY r.id";
$result_total_sampah_selesai = mysqli_query($conn, $query_total_sampah_selesai);

// Simpan total harga per rumah tangga dalam array
$totals_by_household = [];
while ($row = mysqli_fetch_assoc($result_total_sampah_pickup)) {
    $totals_by_household[$row['nama']] = $row['total_harga'];
}

$totals_by_household1 = [];
while ($row = mysqli_fetch_assoc($result_total_sampah_selesai)) {
    $totals_by_household1[$row['nama']] = $row['total_harga'];
}

// Handle POST request untuk update atau hapus sampah
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_sampah = $_POST['id_sampah'];

    // Jika tombol hapus diklik
    if (isset($_POST['action']) && $_POST['action'] == 'hapus') {
        // Query untuk menghapus sampah berdasarkan ID
        $query_delete = "DELETE FROM sampah WHERE id = '$id_sampah'";
        $result_delete = mysqli_query($conn, $query_delete);

        // Cek apakah query berhasil dijalankan
        if ($result_delete) {
            // Redirect ke halaman pengelola setelah penghapusan berhasil
            header("Location: page.php?mod=pengelola");
            exit();
        } else {
            echo "Gagal menghapus sampah. Silakan coba lagi.";
        }
    }

    // Jika tombol selesai diklik
    if (isset($_POST['status']) && $_POST['status'] == 'selesai') {
        $confirmed_by_pengelola = $_POST['confirmed_by_pengelola'];

        // Update status sampah
        $query_update_pengelola = "UPDATE sampah SET confirmed_by_pengelola = 'diterima' WHERE id = '$id_sampah'";
        mysqli_query($conn, $query_update_pengelola);

        // Redirect ke halaman pengelola setelah selesai
        header("Location: page.php?mod=pengelola");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengelola Sampah</title>
    <!-- Link CSS Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <?php include 'assets/components/headerpeng.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Pengelola Sampah Desa Salem</h1>

        <!-- Bagian Sampah Siap Pickup -->
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-primary text-white text-center">
                <h4><i class="fas fa-recycle"></i> Order Sampah Siap Diproses</h4>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($result_sampah) > 0): ?>
                    <?php
                    $current_rw = null;
                    $current_household = null;
                    $total_pickup = 0;
                    while ($sampah = mysqli_fetch_assoc($result_sampah)):
                        if ($current_rw !== $sampah['rw']): ?>
                            <?php if ($current_rw !== null): ?>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total Harga (Rp):</td>
                                    <td class="fw-bold"><?= number_format($total_pickup, 2, ',', '.') ?></td>
                                </tr>
                                </tbody>
                                </table>
                            <?php endif; ?>
                            <h5 class="mt-4 text-decoration-underline">RW: <?= $sampah['rw'] ?></h5>
                            <?php
                            $current_rw = $sampah['rw'];
                            $current_household = null;
                            $total_pickup = 0;
                        endif;

                        if ($current_household !== $sampah['nama']): ?>
                            <?php if ($current_household !== null): ?>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total Harga (Rp):</td>
                                    <td class="fw-bold"><?= number_format($total_pickup, 2, ',', '.') ?></td>
                                </tr>
                                </tbody>
                                </table>
                            <?php endif;
                            $current_household = $sampah['nama'];
                            $total_pickup = 0; ?>
                            <div class="mt-3 p-3 border border-info rounded bg-light">
                                <h6><i class="fas fa-home"></i> Rumah Tangga: <?= $sampah['nama'] ?></h6>
                                <p><i class="fas fa-map-marker-alt"></i> Alamat: <?= $sampah['alamat'] ?></p>
                                <p><i class="fas fa-phone-alt"></i> Kontak: <?= $sampah['kontak'] ?></p>
                            </div>
                            <table class="table table-striped table-hover mt-3">
                                <thead>
                                    <tr class="table-primary">
                                        <th>Jenis Sampah</th>
                                        <th>Berat (kg)</th>
                                        <th>Total Harga (Rp)</th>
                                        <th>Status</th>
                                        <th>Pembayaran Pengelola</th>
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
                                    <button value="selesai" name="status" class="btn btn-success btn-sm"
                                        <?= ($sampah['confirmed_by_pengelola'] == 'diterima') ? 'disabled' : '' ?>>
                                        <i class="fas fa-check"></i> Selesai
                                    </button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id_sampah" value="<?= $sampah['id'] ?>">
                                    <button type="submit" name="action" value="hapus" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                </form>
                                <a href="page.php?mod=edit&id=<?= $sampah['id'] ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-edit"></i> Hitung Sampah
                                </a>
                            </td>
                        </tr>
                        <?php $total_pickup += $sampah['total_harga']; ?>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Total Harga (Rp):</td>
                        <td class="fw-bold"><?= number_format($total_pickup, 2, ',', '.') ?></td>
                    </tr>
                    </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">Tidak ada order sampah yang siap diproses.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Link JS Bootstrap 5 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>
