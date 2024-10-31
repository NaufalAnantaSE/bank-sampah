<?php
include "../bank-sampah/config/connect.php"; // Pastikan file ini sudah termasuk koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id_sampah'];
    
    // Update status sampah
    $query_update_pengelola = "UPDATE sampah SET confirmed_by_pengelola = 'diterima' WHERE id = ?";
    $stmt = $conn->prepare($query_update_pengelola);
    $stmt->bind_param('i', $id_sampah);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
