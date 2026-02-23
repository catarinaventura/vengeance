<?php
include "db.php";

$cart = $_SESSION["cart"] ?? [];

/* --------------------------------------------------------------------
CARREGAR EVENTS DO CARRINHO E CALCULAR TOTAL
-------------------------------------------------------------------- */
$events = [];
$total = 0.0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $types = str_repeat("i", count($ids));

    $sql = "
        SELECT e.id, e.name, e.date, e.price, e.stock, e.image, c.name AS category_name
        FROM events e
        JOIN categories c ON c.id = e.category_id
        WHERE e.is_active = 1 AND e.id IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $eid = (int)$row["id"];
        $qty = (int)($cart[$eid] ?? 0);

        if ($qty <= 0) continue;

        // clamp to stock (just in case)
        $stock = (int)$row["stock"];
        if ($qty > $stock) $qty = $stock;

        $row["qty"] = $qty;
        $row["subtotal"] = (float)$row["price"] * $qty;

        $events[] = $row;
        $total += $row["subtotal"];
    }
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

        <title>Vengeance︱Contactos</title>

        <script src="https://kit.fontawesome.com/e815cc27bb.js" crossorigin="anonymous"></script>
        <script src="scripts/script.js"></script>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="styles/main_style.css">
        <link rel="stylesheet" href="styles/cart_style.css">
        <link rel="icon" type="image/png" href="pictures/logo.png" alt="Vengeance Logo">

    </head>

    <body>

<!-- CABEÇALHO -->

<?php include "header.php"; ?>

<!-- CONTEUDO PRINCIPAL -->

        <img src="pictures/cart.jpg" class="background" alt="Vengeance Background">

        <main>

            <section class=" text-center my-5 section-titulo">
                <h1 class="mb-4">Carrinho</h1>
            </section>

            <div class="container my-5 section-content">
                <?php if (empty($events)): ?>
                    <div class="text-warning mb-3 fw-semibold">O seu carrinho está vazio.</div>
                    <a class="btn btn-outline-light botao-geral" href="events.php">Ver eventos</a>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th>Categoria</th>
                                    <th>Data</th>
                                    <th>Preço</th>
                                    <th style="width: 160px;">Quantidade</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($events as $e): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($e["image"])): ?>
                                                <img src="<?= htmlspecialchars($e["image"]) ?>" alt="event" style="width:60px;height:60px;object-fit:cover;border-radius:10px;">
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($e["name"]) ?></div>
                                                <small class="text-muted">Stock: <?= (int)$e["stock"] ?></small>
                                            </div>
                                        </div>
                                    </td>

                                    <td><?= htmlspecialchars($e["category_name"]) ?></td>

                                    <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($e["date"]))) ?></td>

                                    <td><?= number_format((float)$e["price"], 2, ",", ".") ?>€</td>

                                    <td>
                                        <form class="d-flex gap-2" method="post" action="cart_action.php">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="event_id" value="<?= (int)$e["id"] ?>">
                                                
                                            <input
                                                class="form-control form-control-sm"
                                                type="number"
                                                min="1"
                                                name="quantity"
                                                value="<?= (int)$e["qty"] ?>"
                                            >

                                            <button class="btn btn-sm btn-outline-success" type="submit">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    </td>

                                    <td><?= number_format((float)$e["subtotal"], 2, ",", ".") ?>€</td>

                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-danger"
                                        href="cart_action.php?action=remove&event_id=<?= (int)$e["id"] ?>">
                                            Remover
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                        <a class="btn btn-outline-light" href="events.php">Continuar a comprar</a>

                        <div class="text-end">
                            <div class="fs-5 mb-2">
                                Total: <span class="fw-bold"><?= number_format($total, 2, ",", ".") ?>€</span>
                            </div>

                            <form method="post" action="cart_action.php" class="d-inline">
                                <input type="hidden" name="action" value="checkout">
                                <button class="btn btn-success btn-lg">
                                    Finalizar Compra <i class="fa-solid fa-credit-card ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                <?php endif; ?>
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
