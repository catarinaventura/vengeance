<?php
include __DIR__ . "/../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

/* --------------------------------------------------------------------
CARREGAR UTILIZADORES, COMPRAS, EVENTOS e CATEGORIAS
-------------------------------------------------------------------- */
$usersResult = $conn->query("
    SELECT id, username, email, role
    FROM users
    WHERE role = 'user'
    ORDER BY id DESC
");

$purchasesResult = $conn->query("
    SELECT
        p.id AS purchase_id,
        u.username,
        p.total_amount,
        p.status,
        DATE(p.created_at) AS purchase_date,
        GROUP_CONCAT(
            CONCAT(e.name, ' x', pi.quantity)
            ORDER BY e.name
            SEPARATOR ' | '
        ) AS items_summary
    FROM purchases p
    JOIN users u ON u.id = p.user_id
    JOIN purchase_items pi ON pi.purchase_id = p.id
    JOIN events e ON e.id = pi.event_id
    GROUP BY p.id, u.username, p.total_amount, DATE(p.created_at), p.status
    ORDER BY p.created_at DESC, p.id DESC
");


$eventsResult = $conn->query("
    SELECT e.id, e.name, e.category_id, e.date, e.description, e.image, e.price, e.stock, e.is_active,
           c.name AS category_name
    FROM events e
    JOIN categories c ON e.category_id = c.id
    ORDER BY e.created_at DESC
");

$categoriesResult = $conn->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
");

$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

/* --------------------------------------------------------------------
APAGAR UTILIZADORES
-------------------------------------------------------------------- */
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("
        DELETE FROM users 
        WHERE id = ? AND role = 'user'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['notification'] = "Utilizador apagado com sucesso!";
    $_SESSION['notification_type'] = "danger";

    header("Location: admin.php");
    exit;
}

/* --------------------------------------------------------------------
EDITAR UTILIZADORES
-------------------------------------------------------------------- */
if (isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $username = trim($_POST['username'][$id]);
    $email = trim($_POST['email'][$id]);

    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $email, $id);

    if($stmt->execute()){
        $_SESSION['notification'] = "Informações do utilizador atualizadas com sucesso!";
        $_SESSION['notification_type'] = "success";
    } else {
        $_SESSION['notification'] = "Erro ao atualizar o utilizador.";
        $_SESSION['notification_type'] = "danger";
    }

    header("Location: admin.php");
    exit;
}

/* --------------------------------------------------------------------
ESTADO DAS COMPRAS
-------------------------------------------------------------------- */
if (isset($_POST['update_purchase_id'], $_POST['new_status'])) {
    $purchase_id = (int) $_POST['update_purchase_id'];
    $new_status = $_POST['new_status'];

    // Estado atual da compra
    $current = $conn->prepare("SELECT status FROM purchases WHERE id = ?");
    $current->bind_param("i", $purchase_id);
    $current->execute();
    $res = $current->get_result();
    $purchase = $res->fetch_assoc();
    $current->close();

    if (!$purchase) {
        header("Location: admin.php");
        exit;
    }

    // Prevenir a mudança de estado se já estiver cancelado ou concluído
    if ($purchase['status'] === "cancelled" || $purchase['status'] === "paid") {
        header("Location: admin.php");
        exit;
    }

    // Atualizar o estado da compra
    $update = $conn->prepare(
        "UPDATE purchases SET status = ? WHERE id = ?"
    );
    $update->bind_param("si", $new_status, $purchase_id);

    if ($update->execute()) {
        $_SESSION['notification'] = "Estado da compra atualizado com sucesso!";
        $_SESSION['notification_type'] = "success";
    } else {
        $_SESSION['notification'] = "Erro ao atualizar o estado da compra.";
        $_SESSION['notification_type'] = "danger";
    }

    $update->close();

    header("Location: admin.php");
    exit;
}

$statusLabels = [
    'paid'      => 'Paga',
    'pending'   => 'Pendente',
    'cancelled' => 'Cancelada'
];

/* --------------------------------------------------------------------
ADICIONAR E EDITAR EVENTOS
-------------------------------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["event_action"])) {

    // ADICIONAR EVENTO
    if ($_POST["event_action"] === "add_event") {
        $name = trim($_POST["name"]);
        $description = trim($_POST["description"]);
        $category_id = (int) $_POST["category_id"];
        $date = $_POST["date"];
        $price = floatval($_POST["price"]);
        $stock = intval($_POST["stock"]);

        // IMAGE UPLOAD
        $imagePath = null;

        if (isset($_FILES["image"]) && $_FILES["image"]["error"] === 0) {
            $uploadDir = "../uploads/events/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "gif", "webp"];

            if (in_array($ext, $allowed)) {
                $filename = uniqid("event_", true) . "." . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
                    $imagePath = "uploads/events/" . $filename;
                }
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO events (name, category_id, description, date, price, stock, image, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->bind_param("sissdis", $name, $category_id, $description, $date, $price, $stock, $imagePath);
        $stmt->execute();

        $_SESSION['notification'] = "Evento adicionado com sucesso!";
        $_SESSION['notification_type'] = "success";

        header("Location: admin.php");
        exit;
    }

    // EDITAR EVENTO
    if ($_POST["event_action"] === "edit_event" && isset($_POST['edit_event_id'])) {
        $id = (int) $_POST['edit_event_id'];

        $name = trim($_POST['name'][$id]);
        $category_id = (int) $_POST['category_id'][$id];
        $date = $_POST['date'][$id];
        $price = (float) $_POST['price'][$id];
        $stock = (int) $_POST['stock'][$id];

        $stmt = $conn->prepare("
            UPDATE events
            SET name=?, category_id=?, date=?, price=?, stock=?
            WHERE id=?
        ");
        $stmt->bind_param("sisdii", $name, $category_id, $date, $price, $stock, $id);
        $stmt->execute();

        $_SESSION['notification'] = "Evento atualizado com sucesso!";
        $_SESSION['notification_type'] = "success";

        header("Location: admin.php");
        exit;
    }

}

/* --------------------------------------------------------------------
DESATIVAR EVENTOS
-------------------------------------------------------------------- */
if (isset($_GET['deactivate_event_id'])) {
    $id = intval($_GET['deactivate_event_id']);

    $stmt = $conn->prepare("UPDATE events SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['notification'] = "Evento desativado com sucesso!";
    $_SESSION['notification_type'] = "success";

    header("Location: admin.php");
    exit;
}

/* --------------------------------------------------------------------
REATIVAR EVENTOS
-------------------------------------------------------------------- */
if (isset($_GET['reactivate_event_id'])) {
    $id = intval($_GET['reactivate_event_id']);

    $stmt = $conn->prepare("UPDATE events SET is_active = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['notification'] = "Evento reativado com sucesso!";
    $_SESSION['notification_type'] = "success";

    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Bem-vindo à Vengance, onde podes comprar bilhetes para os maiores gaming events de Portugal Continental!">
    <meta name="author" content="Catarina Ventura">
    <meta name="keywords" content="Vengeance, Gaming, Events, Torneios, E-Sports, Portugal, Bilhetes">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vengeance︱Admin</title>

    <script src="https://kit.fontawesome.com/e815cc27bb.js" crossorigin="anonymous"></script>

    <base href="/projetofinal/">

    <script src="scripts/script.js"></script>
    <script src="scripts/admin_script.js" defer></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/main_style.css">
    <link rel="stylesheet" href="styles/user_style.css">
    <link rel="icon" type="image/png" href="pictures/logo.png" alt="Vengeance Logo">

</head>

<body>

<!-- CABEÇALHO -->

<?php include __DIR__ . "/../header.php"; ?>

<!-- CONTEUDO PRINCIPAL -->

    <img src="pictures/panel.jpg" class="background" alt="Vengeance Background">

    <main>

            <section class="intro text-center my-5 section-titulo">
                <h1 class="mb-4 text-center">Painel de Administrador</h1>
                <p>Bem-vindo, <?= htmlspecialchars($_SESSION['username']) ?>! Aqui pode gerir os utilizadores, as compras e os eventos da plataforma.</p>
            </section>

            <section class="container my-5 section-content">

            <!-- TABS -->
            <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">

                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">Utilizadores</button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button">Compras</button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button">Eventos</button>
                </li>

                <li class="nav-item ms-auto">
                    <a href="users/logout.php" class="btn btn-danger botao-geral">Logout</a>
                </li>

            </ul>

            <div class="tab-content" id="adminTabsContent">

            <!-- USERS -->
            <div class="tab-pane fade show active text-center" id="users">
                <table class="table table-striped mb-4">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                        <tr>
                            
                            <td><?= $user['id'] ?></td>

                            <td class="username">
                                <?= htmlspecialchars($user['username']) ?>
                            </td>

                            <td class="email">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>

                            <td>
                                <button type="button"
                                        class="btn btn-sm edit-user-btn botao-geral"
                                        data-id="<?= $user['id'] ?>">
                                    Editar
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-success save-user-btn"
                                        data-id="<?= $user['id'] ?>"
                                        style="display:none;">
                                    Guardar
                                </button>

                                <a href="users/admin.php?delete_id=<?= $user['id'] ?>"
                                class="btn btn-sm btn-danger botao-apagar"
                                onclick="return confirm('Tens certeza que queres eliminar este utilizador?')">
                                X
                                </a> 
                            </td>

                        </tr>
                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

            <!-- PURCHASES -->
            <div class="tab-pane fade text-center" id="purchases">

                <div class="d-flex justify-content-center mb-3">
                    <input id="purchaseSearch" type="text" class="form-control w-50"
                        placeholder="Pesquisar compras (utilizador, itens, estado, ID...)">
                </div>

                <table class="table table-striped mb-4">

                    <thead>
                        <tr>
                            <th>Compra</th>
                            <th>Utilizador</th>
                            <th>Itens</th>
                            <th>Total</th>
                            <th>Data</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($purchase = $purchasesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$purchase['purchase_id'] ?></td>
                                <td><?= htmlspecialchars($purchase['username']) ?></td>

                                <td>
                                    <?= htmlspecialchars($purchase['items_summary']) ?>
                                </td>

                                <td><?= number_format((float)$purchase['total_amount'], 2, ",", ".") ?> €</td>

                                <td><?= htmlspecialchars(date("d/m/Y", strtotime($purchase['purchase_date']))) ?></td>

                                <td>
                                    <?php
                                    $status = $purchase['status'];
                                    $label  = $statusLabels[$status] ?? ucfirst($status);
                                    ?>

                                    <span class="status-<?= htmlspecialchars($status) ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($purchase['status'] === "pending"): ?>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="update_purchase_id" value="<?= (int)$purchase['purchase_id'] ?>">

                                            <select name="new_status" class="form-select form-select-sm">
                                                <option value="pending" selected>Pendente</option>
                                                <option value="paid">Pago</option>
                                            </select>

                                            <button type="submit" class="btn btn-success btn-sm botao-geral">
                                                Atualizar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="fw-bold"><?= ucfirst($purchase['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>

            <!-- EVENTS -->
            <div class="tab-pane fade text-center" id="events">
                <table class="table table-striped mb-4">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Preço</th>
                            <th>Stock</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php while ($event = $eventsResult->fetch_assoc()): ?>
                            <tr data-category-id="<?= (int)$event['category_id'] ?>">
                                <td>
                                    <?= $event["id"] ?>
                                    <?php if (!$event['is_active']): ?>
                                        <span class="badge bg-secondary ms-2">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($event["name"]) ?></td>
                                <td><?= htmlspecialchars($event["category_name"]) ?></td>
                                <td><?= htmlspecialchars($event["date"]) ?></td>
                                <td>€<?= htmlspecialchars($event["price"]) ?></td>
                                <td><?= htmlspecialchars($event["stock"]) ?></td>

                                <td>
                                    <!-- EDITAR EVENTO -->
                                    <button type="button"
                                            class="btn btn-warning btn-sm edit-event-btn botao-geral"
                                            data-id="<?= $event['id'] ?>">
                                        Editar
                                    </button>

                                    <?php if ($event['is_active']): ?>
                                        <!-- DESATIVAR -->
                                        <a href="users/admin.php?deactivate_event_id=<?= $event['id'] ?>"
                                        class="btn btn-danger btn-sm botao-apagar"
                                        onclick="return confirm('Desativar este evento?')">
                                        Desativar
                                        </a>
                                    <?php else: ?>
                                        <!-- REATIVAR -->
                                        <a href="users/admin.php?reactivate_event_id=<?= $event['id'] ?>"
                                        class="btn btn-success btn-sm botao-geral"
                                        onclick="return confirm('Reativar este evento?')">
                                        Ativar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                </table>

                <h5 class="mt-4">Adicionar Evento</h5>
                <form method="POST" enctype="multipart/form-data" class="col-md-6 mx-auto mb-3">
                    
                    <input type="hidden" name="event_action" value="add_event">
                    
                    <input type="text" name="name" class="form-control mb-2" placeholder="Nome do evento" required>
                    
                    <textarea name="description" class="form-control mb-2" placeholder="Descrição"></textarea>
                    
                    <select name="category_id" class="form-control mb-2" required>

                        <option value="" disabled selected>Categoria</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>

                    </select>

                    <input type="datetime-local" name="date" class="form-control mb-2" required>
                    
                    <input type="number" step="0.01" name="price" class="form-control mb-2" placeholder="Preço (€)" required>
                    
                    <input type="number" name="stock" class="form-control mb-2" placeholder="Stock" required>
                    
                    <input type="file" name="image" class="form-control mb-2" accept="image/*">
                    
                    <button type="submit" class="btn btn-primary botao-geral">Adicionar Evento</button>
                
                </form>

            </div>

        </div>

    </main>

    <!-- Notificações (AJAX) -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <div id="cart-toast" class="toast align-items-center text-bg-success border-0"
            role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
            <div class="toast-body" id="cart-toast-message">
                <!-- Mensagem -->
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

<!-- RODAPE -->

        <footer class="text-center py-4 rodapé">

            <p>&copy; <span class="highlights">Catarina Ventura</span> e <span class="highlights">Vengeance</span>. Todos os direitos reservados.</p>

        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            window.CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;

            const userRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?>";
            const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        </script>  
        
        <?php if(isset($_SESSION['notification']) && $_SESSION['notification'] != ''): ?>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
            <div id="actionToast" class="toast align-items-center text-bg-<?= $_SESSION['notification_type'] ?> border-0"
                role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?= htmlspecialchars($_SESSION['notification']) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <script>
            const toastEl = document.getElementById('actionToast');
            if(toastEl) {
                const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                toast.show();
            }
        </script>

        <?php 
            unset($_SESSION['notification']);
            unset($_SESSION['notification_type']);
        ?>
        <?php endif; ?>

    </body>

</html>