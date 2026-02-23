<?php
include __DIR__ . "/../db.php";

/* --------------------------------------------------------------------
EDITAR PERFIL (username, email, password opcional)
-------------------------------------------------------------------- */
if (isset($_POST['update_profile'])) {
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $user_id = (int)$_SESSION["user_id"];

    $new_username = trim($_POST['username'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // validações básicas
    if ($new_username === '' || $new_email === '') {
        $_SESSION['notification'] = "Username e email são obrigatórios.";
        $_SESSION['notification_type'] = "danger";
        header("Location: profile.php");
        exit;
    }

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['notification'] = "Email inválido.";
        $_SESSION['notification_type'] = "danger";
        header("Location: profile.php");
        exit;
    }

    // confirmar password (se preencherem)
    $changePassword = ($new_password !== '' || $confirm_pass !== '');
    if ($changePassword) {
        if ($new_password !== $confirm_pass) {
            $_SESSION['notification'] = "As palavras-passe não coincidem.";
            $_SESSION['notification_type'] = "danger";
            header("Location: profile.php");
            exit;
        }
        if (strlen($new_password) < 6) {
            $_SESSION['notification'] = "A palavra-passe deve ter pelo menos 6 caracteres.";
            $_SESSION['notification_type'] = "danger";
            header("Location: profile.php");
            exit;
        }
    }

    // garantir username/email únicos (menos o próprio utilizador)
    $check = $conn->prepare("
        SELECT id
        FROM users
        WHERE (username = ? OR email = ?) AND id <> ?
        LIMIT 1
    ");
    $check->bind_param("ssi", $new_username, $new_email, $user_id);
    $check->execute();
    $checkRes = $check->get_result();

    if ($checkRes->num_rows > 0) {
        $_SESSION['notification'] = "Esse username ou email já está a ser usado.";
        $_SESSION['notification_type'] = "danger";
        header("Location: profile.php");
        exit;
    }

    // update
    if ($changePassword) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET username = ?, email = ?, password = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $new_username, $new_email, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE users
            SET username = ?, email = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['username'] = $new_username; // para o header ficar atualizado
        $_SESSION['notification'] = "Dados atualizados com sucesso! ✨";
        $_SESSION['notification_type'] = "success";
    } else {
        $_SESSION['notification'] = "Erro ao atualizar os dados.";
        $_SESSION['notification_type'] = "danger";
    }

    header("Location: profile.php");
    exit;
}


/* --------------------------------------------------------------------
APAGAR CONTA
-------------------------------------------------------------------- */
if (isset($_POST['delete_account'])) {
    $user_id = $_SESSION['user_id'];

    $conn->begin_transaction();

    // Apagar itens das compras do utilizador
    $stmt = $conn->prepare("
        DELETE pi FROM purchase_items pi
        JOIN purchases p ON pi.purchase_id = p.id
        WHERE p.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Apagar compras do utilizador
    $stmt = $conn->prepare("DELETE FROM purchases WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Apagar o utilizador
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // Logout automático
    $_SESSION['notification'] = "Conta apagada com sucesso!";
    $_SESSION['notification_type'] = "danger";

    unset($_SESSION['user_id']);

    header("Location: index.php");
    exit;
}

/* --------------------------------------------------------------------
COMPRAS
-------------------------------------------------------------------- */
if (isset($_POST['delete_purchase_id'])) {
    $purchase_id = (int) $_POST['delete_purchase_id'];
    $user_id     = $_SESSION['user_id'];

    // Confirmar que a compra é do utilizador e está pendente
    $check = $conn->prepare("
        SELECT id 
        FROM purchases 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $check->bind_param("ii", $purchase_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 1) {

        $conn->begin_transaction();

        // Carregar os itens da compra
        $items = $conn->prepare("
            SELECT event_id, quantity 
            FROM purchase_items 
            WHERE purchase_id = ?
        ");
        $items->bind_param("i", $purchase_id);
        $items->execute();
        $items_result = $items->get_result();

        // Repor stock
        while ($item = $items_result->fetch_assoc()) {
            $updateStock = $conn->prepare("
                UPDATE events 
                SET stock = stock + ? 
                WHERE id = ?
            ");
            $updateStock->bind_param(
                "ii",
                $item['quantity'],
                $item['event_id']
            );
            $updateStock->execute();
        }

        // Marcar compra como cancelada
        $updatePurchase = $conn->prepare("
            UPDATE purchases 
            SET status = 'cancelled' 
            WHERE id = ?
        ");
        $updatePurchase->bind_param("i", $purchase_id);
        $updatePurchase->execute();

        $conn->commit();
    }

    $_SESSION['notification'] = "Compra cancelada com sucesso!";
    $_SESSION['notification_type'] = "success";
    header("Location: profile.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = "";
$email = "";

$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $username = $row["username"];
    $email = $row["email"];
}

// Carregar compras do utilizador
$purchases = [];

$sql = "
    SELECT p.id, p.created_at, p.status, p.total_amount
    FROM purchases p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $purchases[] = $row;
}

function getPurchaseItems($conn, $purchase_id) {
    $items = [];

    $sql = "
        SELECT 
            pi.event_id,
            e.name,
            e.price AS unit_price,
            pi.quantity,
            (e.price * pi.quantity) AS subtotal
        FROM purchase_items pi
        JOIN events e ON e.id = pi.event_id
        WHERE pi.purchase_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $purchase_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
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

    <title>Vengeance︱Registo</title>

    <script src="https://kit.fontawesome.com/e815cc27bb.js" crossorigin="anonymous"></script>

    <base href="/projetofinal/">

    <script src="scripts/script.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/main_style.css">
    <link rel="stylesheet" href="styles/user_style.css">
    <link rel="icon" type="image/png" href="pictures/logo.png" alt="Vengeance Logo">

</head>

<body>

<!-- CABEÇALHO -->

<?php include __DIR__ . "/../header.php"; ?>

<!-- CONTEUDO PRINCIPAL -->

    <img src="pictures/login_register.jpg" class="background" alt="Vengeance Background">


    <main>
        <section class="intro text-center my-5 section-titulo">
            <h1>Bem-vindo, <?= htmlspecialchars($username) ?>!</h1>
        </section>

        <section class="container my-5 section-content">

        <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">

            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">Compras</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button">Definições</button>
            </li>

            <li class="nav-item ms-auto">
                <a href="users/logout.php" class="btn btn-danger botao-geral">Logout</a>
            </li>

        </ul>

        <div class="tab-content" id="adminTabsContent">

        <!-- COMPRAS -->

            <div class="tab-pane fade show active text-center" id="history" role="tabpanel">

                <h2>Histórico de Compras</h2>

                <?php if (empty($purchases)): ?>
                    <p class="mt-3">Ainda não fez nenhuma compra.</p>
                    <p class="mt-3">Adicione bilhetes ao seu carrinho <a class="links" href="events.php">aqui</a>!</p>
                <?php else: ?>

                    <?php foreach ($purchases as $purchase): ?>
                        <div class="card my-4 shadow-sm">
                            <div class="card-body">

                                <h5 class="card-title">
                                    Compra #<?= $purchase['id'] ?>
                                </h5>

                                <p class="card-text">
                                    <strong>Data:</strong>
                                    <?= date("d/m/Y H:i", strtotime($purchase['created_at'])) ?>
                                </p>

                                <p class="card-text">
                                    <strong>Estado:</strong>
                                    <?php if ($purchase['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pendente</span>
                                    <?php endif; ?>
                                    <?php if ($purchase['status'] === 'paid'): ?>
                                        <span class="badge bg-success">Paga</span>
                                    <?php endif; ?>
                                    <?php if ($purchase['status'] === 'cancelled'): ?>
                                        <span class="badge bg-secondary">Cancelada</span>
                                    <?php endif; ?>
                                    <?php if ($purchase['status'] === 'refunded'): ?>
                                        <span class="badge bg-info text-dark">Reembolsada</span>
                                    <?php endif; ?>
                                </p>

                                <?php if ($purchase['status'] === 'pending'): ?>
                                    <form method="POST" class="mt-3"
                                        onsubmit="return confirm('Tens a certeza que queres cancelar esta compra?');">
                                        <input type="hidden" name="delete_purchase_id" value="<?= $purchase['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm botao-apagar">
                                            Cancelar compra
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <hr>

                                <ul class="list-group list-group-flush">
                                    <?php
                                        $items = getPurchaseItems($conn, $purchase['id']);
                                        foreach ($items as $item):
                                    ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>
                                                <?= htmlspecialchars($item['name']) ?>
                                                × <?= $item['quantity'] ?>
                                            </span>
                                            <span>
                                                <?= number_format($item['subtotal'], 2, ",", ".") ?> €
                                            </span>
                                        </li>

                                    <?php endforeach; ?>
                                    <li class="list-group-item d-flex justify-content-between fw-bold">
                                        <span>Total</span>
                                        <span><?= number_format($purchase['total_amount'], 2) ?> €</span>
                                    </li>
                                </ul>

                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        <!-- DEFINICOES -->

            <div class="tab-pane fade text-center" id="account" role="tabpanel">

                <h2 class="mb-4">Definições</h2>

                <form method="POST" class="col-md-6 mx-auto text-start">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control"
                            value="<?= htmlspecialchars($username) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <hr class="my-4">

                    <p class="text mb-10 highlights text-center">Alterar palavra-passe (opcional)</p>

                    <div class="mb-3">
                        <label class="form-label">Nova palavra-passe</label>
                        <input type="password" name="password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar palavra-passe</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-success botao-geral w-100">
                        Guardar alterações
                    </button>
                </form>

                <hr>

                <p class="text mb-10 highlights text-center">Apagar conta e todos os seus dados e encomendas</p>

                <form method="POST" onsubmit="return confirm('Tens a certeza que queres apagar a tua conta?');">
                    <button type="submit" name="delete_account" class="btn btn-danger botao-geral">
                        Apagar Conta
                    </button>
                </form>

            </div>

        </div>

    </main>

    <!-- FAQ -->
            <section class="container my-5 section-content" id="faq">
                <h2 class="mb-4">Perguntas Frequentes</h2>

                <div class="accordion" id="faqAccordion">

                    <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading1">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1">
                        Preciso de conta para comprar bilhetes?
                        </button>
                    </h2>
                    <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                        Sim. Apenas utilizadores registados podem adicionar eventos ao carrinho e concluir compras.
                        Isto permite guardar o seu histórico de compras e gerir as mesmas.
                        </div>
                    </div>
                    </div>

                    <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                        Onde posso ver as minhas compras?
                        </button>
                    </h2>
                    <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                        Na área de perfil, tens acesso ao teu histórico de compras. Lá pode consultar os eventos adquiridos, o valor total
                        e o estado da compra.
                        </div>
                    </div>
                    </div>

                    <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                        Posso cancelar uma compra?
                        </button>
                    </h2>
                    <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                        Compras com estado <strong>“pendente”</strong> podem ser canceladas através da área de perfil.
                        Após confirmação ou processamento, a compra já não poderá ser anulada.
                        </div>
                    </div>
                    </div>

                    <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                        O que significam os diferentes estados da compra?
                        </button>
                    </h2>
                    <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                        <ul class="mb-0">
                            <li><strong>Pendente</strong> – A compra foi criada, mas ainda não foi processada nem paga.</li>
                            <li><strong>Paga</strong> – A compra foi validada e paga com sucesso.</li>
                            <li><strong>Cancelada</strong> – A compra foi anulada.</li>
                        </ul>
                        </div>
                    </div>
                    </div>

                </div>
            </section>

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