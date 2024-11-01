<?php
session_start();
require 'config/connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: page.php?mod=home");
    exit();
}

$id_warung = $_SESSION['user']['id'];

// Menampilkan saldo warung mitra dengan prepared statement
$stmt_saldo = $conn->prepare("SELECT saldo FROM warung_mitra WHERE id = ?");
$stmt_saldo->bind_param("i", $id_warung);
$stmt_saldo->execute();
$result_saldo = $stmt_saldo->get_result();
$saldo1 = $result_saldo->fetch_assoc()['saldo'];
$stmt_saldo->close();

$successMessage = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_penarikan'])) {
    $jumlah_penarikan = floatval($_POST['jumlah']);

    // Validasi jumlah penarikan
    if ($jumlah_penarikan <= 0) {
        $errorMessage = 'Jumlah penarikan harus lebih besar dari nol!';
    } elseif ($jumlah_penarikan > $saldo1) {
        $errorMessage = 'Saldo tidak mencukupi!';
    } else {
        // Masukkan transaksi penarikan
        $stmt_insert = $conn->prepare("INSERT INTO transaksi_pencairan (id_warung_mitra, jumlah, status) VALUES (?, ?, 'pending')");
        $stmt_insert->bind_param("id", $id_warung, $jumlah_penarikan);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Tampilkan pesan sukses
        $successMessage = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warung Mitra - Penarikan Saldo</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 600px;
        }
        .modal-footer .btn {
            min-width: 100px;
        }
        .saldo-section, .form-group, .modal-content {
            border-radius: 10px;
        }
    </style>
</head>
<body>
<?php include 'assets/components/headerwarung.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center">Penarikan Saldo</h1>
        <div class="saldo-section p-3 mb-4 bg-light text-center">
            <p>Saldo Anda saat ini: <strong>Rp. <?= number_format($saldo1, 2, ',', '.') ?></strong></p>
        </div>

        <form id="withdrawalForm" method="POST" onsubmit="return validateAmount()">
            <div class="form-group">
                <label for="jumlah">Jumlah Penarikan (Rp)</label>
                <input type="number" class="form-control" name="jumlah" id="jumlah" required>
            </div>

            <button type="button" class="btn btn-primary btn-block" onclick="showConfirmationModal()">Ajukan Penarikan</button>
            <input type="hidden" name="confirm_penarikan" value="1">
        </form>
    </div>
    

    <!-- Modal Konfirmasi Penarikan -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Penarikan Saldo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center" id="confirmMessage">
                    Apakah Anda yakin ingin melakukan penarikan saldo sebesar <strong id="amount"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitWithdrawal()">Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>
     <!-- Modal Sukses -->
     <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <h5>Permintaan pencairan saldo berhasil!</h5>
                    <p>Menunggu konfirmasi dari pengelola.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function showConfirmationModal() {
            var jumlahPenarikan = document.getElementById('jumlah').value;
            if (jumlahPenarikan <= 0) {
                alert('Jumlah penarikan harus lebih besar dari nol!');
                return;
            }
            document.getElementById('amount').textContent = "Rp " + new Intl.NumberFormat('id-ID').format(jumlahPenarikan);
            $('#confirmModal').modal('show');
        }

        function submitWithdrawal() {
            $('#confirmModal').modal('hide');
            document.getElementById('withdrawalForm').submit();
        }

        <?php if ($successMessage): ?>
        $(document).ready(function() {
            $('#successModal').modal('show');
            setTimeout(function() {
                window.location.href = 'page.php?mod=warung';
            }, 3000);
        });
        <?php endif; ?>
    </script>
</body>
</html>
