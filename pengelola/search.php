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

// Proses pencarian rumah tangga berdasarkan nama
$search_query = "";
if (isset($_POST['search']) || isset($_GET['search'])) {
    $search_query = isset($_POST['search']) ? $_POST['search'] : $_GET['search'];
}

// Ambil data sampah yang siap untuk di-pickup, sesuai dengan pencarian rumah tangga
$query_sampah = "SELECT s.*, r.nama, r.rw, r.alamat, r.kontak, js.nama_jenis AS jenis_sampah 
                 FROM sampah s 
                 JOIN rumah_tangga r ON s.id_rumah_tangga = r.id 
                 JOIN jenis_sampah js ON s.id_jenis_sampah = js.id
                 WHERE s.status = 'siap hitung' AND r.nama LIKE '%$search_query%'";
$query_sampah .= " ORDER BY r.rw, r.nama"; // Urutkan berdasarkan RW dan nama rumah tangga
$result_sampah = mysqli_query($conn, $query_sampah);

// Handle POST request untuk update atau hapus sampah
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_sampah'])) {
    $id_sampah = $_POST['id_sampah'];

    // Jika tombol hapus diklik
    if (isset($_POST['action']) && $_POST['action'] == 'hapus') {
        // Query untuk menghapus sampah berdasarkan ID
        $query_delete = "DELETE FROM sampah WHERE id = '$id_sampah'";
        $result_delete = mysqli_query($conn, $query_delete);

        // Cek apakah query berhasil dijalankan
        if ($result_delete) {
            // Redirect ke halaman search setelah penghapusan berhasil
            header("Location: page.php?mod=search&search=" . urlencode($search_query));
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

        // Redirect ke halaman search setelah selesai
        header("Location: page.php?mod=search&search=" . urlencode($search_query));
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php include 'assets/components/headerpeng.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Pengelola Sampah Desa Salem</h1>

        <!-- Form Pencarian -->
        <form onsubmit="redirectToSearch(event)" class="mb-4">
            <div class="form-group row">
                <label for="search" class="col-sm-2 col-form-label">Cari Rumah Tangga:</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Masukkan Nama Rumah Tangga" value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <div class="col-sm-2">
                    <button type="button" onclick="redirectToSearch(event)" class="btn btn-primary">Cari</button>
                </div>
            </div>
        </form>

        <script>
            function redirectToSearch(event) {
                event.preventDefault(); // Mencegah submit form
                const query = document.getElementById("search").value;
                if (query) {
                    window.location.href = `page.php?mod=search&search=${encodeURIComponent(query)}`;
                }
            }
        </script>

        <!-- Bagian Sampah Siap Pickup -->
        <h4 class="mt-4">Order Sampah</h4>

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
                                    <button value="selesai" name="status" class="btn btn-success mt-2"
                                        <?= ($sampah['confirmed_by_pengelola'] == 'diterima') ? 'enabled' : '' ?>>Selesai</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id_sampah" value="<?= $sampah['id'] ?>">
                                    <button type="submit" name="action" value="hapus" class="btn btn-danger mt-2">Hapus</button>
                                </form>
                                <a href="page.php?mod=edit2&id=<?= $sampah['id'] ?>" class="btn btn-info mt-2">Hitung Sampah</a>
                            </td>
                        </tr>
                        <?php $total_pickup += $sampah['total_harga']; ?>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total Harga (Rp):</strong></td>
                        <td><strong><?= number_format($total_pickup, 2, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                    </tbody>
                    </table>
        <?php else: ?>
            <p class="text-center">Tidak ada order sampah yang sesuai dengan pencarian.</p>
        <?php endif; ?>
    </div>
</body>

</html>
