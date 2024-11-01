<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <style>
        /* General Styling */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .navbar {
            background-color: #1A1A19; /* Warna navbar lebih gelap */
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Logo Styling */
        .navbar-brand img {
            width: 90px;
            height: auto;
        }

        /* Navbar Links */
        .navbar-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-item {
            margin-left: 1.2rem;
            text-align: center;
        }

        .nav-link {
            width: 140px;
            color: #ffffff;
            font-size: 0.95rem;
            padding: 2rem 1rem;
            text-decoration: none;
            background-color: #859F3D; /* Warna biru untuk tombol */
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .nav-link:hover {
            background-color: #0056b3;
            color: #ffffff; /* Warna biru lebih gelap saat hover */
        }

        /* Hamburger Icon for Mobile */
        .navbar-toggler {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .navbar-toggler-icon {
            width: 25px;
            height: 3px;
            background-color: #fff;
            margin: 4px 0;
            display: block;
        }

        /* Responsive Styling */
        @media (max-width: 992px) {
            .navbar-nav {
                display: none;
                flex-direction: column;
                background-color: #333;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                padding: 0;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
            }

            .navbar-nav.active {
                display: flex;
            }

            .navbar-toggler {
                display: block;
            }

            .nav-item {
                width: 100%;
                text-align: center;
                border-top: 1px solid #444;
                margin: 0;
            }

            .nav-link {
                width: 100%;
                padding: 1rem 0;
                font-size: 1.1rem;
            }
        }

        /* Desktop: Horizontal Layout */
        @media (min-width: 992px) {
            .navbar-nav {
                flex-direction: row;
                align-items: center;
            }

            .nav-item {
                margin-left: 0.8rem;
            }

            .nav-link {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <!-- Logo -->
        <a class="navbar-brand" href="#">
            <img src="assets/img/logo.png" alt="Logo">
        </a>

        <!-- Hamburger button for mobile -->
        <button class="navbar-toggler" onclick="toggleMenu()">
            <span class="navbar-toggler-icon"></span>
            <span class="navbar-toggler-icon"></span>
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <ul class="navbar-nav" id="navbarNav">
            <li class="nav-item">
                <a href="page.php?mod=search" class="nav-link">Cari</a>
            </li>
            <li class="nav-item">
                <a href="page.php?mod=pengelola" class="nav-link">Home</a>
            </li>
            <li class="nav-item">
                <a href="page.php?mod=data-penarikan" class="nav-link">Data Penarikan</a>
            </li>
            <li class="nav-item">
                <a href="page.php?mod=edit-sampah" class="nav-link">Edit Harga Sampah</a>
            </li>
            <li class="nav-item">
                <a href="page.php?mod=verify" class="nav-link">Daftar User</a>
            </li>
            <li class="nav-item">
                <a href="page.php?mod=verify-war" class="nav-link">Daftar Mitra</a>
            </li>
            <li class="nav-item">
                <a href="page.php?mod=history" class="nav-link">History</a>
            </li>
        </ul>
    </nav>

    <script>
        // Toggle the mobile menu when the hamburger button is clicked
        function toggleMenu() {
            const navbarNav = document.getElementById('navbarNav');
            navbarNav.classList.toggle('active');
        }
    </script>

</body>

</html>
