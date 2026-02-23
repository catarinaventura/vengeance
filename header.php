<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = "";

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
}

?>

<header>
    <nav class="navbar navbar-expand-lg cabeçalho">
        <img src="pictures/logo.png" id="logo" alt="Vengeance Logo">

            <ul class="navbar-nav menu">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="events.php">Eventos</a></li>
                <li class="nav-item"><a class="nav-link" href="contacts.php">Contactos</a></li>
            </ul>

            <ul class="navbar-nav ms-auto nav-icons">

                <?php if (isset($_SESSION["user_id"])): ?>

                    <?php if ($_SESSION["role"] === "admin"): ?>
                        <!-- APENAS PARA ADMINS -->
                        <li class="nav-item">
                            <a class="nav-link" href="users/admin.php">
                                Admin <i class="fa-solid fa-screwdriver-wrench"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- APENAS PARA USERS -->
                        <li class="nav-item">
                            <a class="nav-link" href="users/profile.php">
                                <?= htmlspecialchars($username) ?> <i class="fa-regular fa-user"></i>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                Carrinho <i class="fa-solid fa-cart-arrow-down"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php else: ?>
                        <!-- SEM LOGIN -->
                        <li class="nav-item">
                            <a class="nav-link" href="users/login.php">
                                Login <i class="fa-regular fa-user"></i>
                            </a>
                        </li>
                                
                    <?php endif; ?>

            </ul>

        </div>
    </nav>
</header>