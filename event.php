<?php
include "db.php";

$eventId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($eventId <= 0) {
    header("Location: events.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT e.id, e.name, e.date, e.description, e.image, e.price, e.stock, e.is_active,
           c.name AS category_name
    FROM events e
    JOIN categories c ON e.category_id = c.id
    WHERE e.id = ? AND e.is_active = 1
    LIMIT 1
");

$stmt->bind_param("i", $eventId);
$stmt->execute();

$result = $stmt->get_result();
$event = $result->fetch_assoc();

$stmt->close();

if (!$event) {
    http_response_code(404);
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vengeance︱Evento</title>

    <script src="https://kit.fontawesome.com/e815cc27bb.js" crossorigin="anonymous"></script>
    <script src="scripts/script.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/main_style.css">
    <link rel="stylesheet" href="styles/events_style.css">
    <link rel="icon" type="image/png" href="pictures/logo.png" alt="Vengeance Logo">
</head>

<body>

<?php include "header.php"; ?>

<img src="pictures/events.jpg" class="background" alt="Vengeance Background">

<main class="ev-page ev-single">

    <div class="container my-5">
        <?php if (!$event): ?>

            <div class="alert alert-danger">
                Evento não encontrado.
            </div>
            <a class="btn btn-outline-light" href="events.php">← Voltar aos eventos</a>

        <?php else: ?>

            <?php
                $img = $event["image"] ? str_replace("../", "", $event["image"]) : "pictures/events.jpg";
                $dateFormatted = date("d/m/Y H:i", strtotime($event["date"]));
            ?>

            <section class="ev-single-wrap">

                <img src="<?= htmlspecialchars($img) ?>" class="ev-single-img" alt="<?= htmlspecialchars($event["name"]) ?>">

                <div class="ev-single-body text-center">

                    <h1 class="ev-single-title mb-2"><?= htmlspecialchars($event["name"]) ?></h1>

                    <div class="mb-3">
                        <span class="ev-pill"><?= htmlspecialchars($event["category_name"]) ?></span>
                    </div>

                    <p class="ev-single-date mb-3">
                        <i class="fa-regular fa-calendar"></i>
                        <?= $dateFormatted ?>
                    </p>

                    <p class="ev-single-desc mb-4">
                        <?= nl2br(htmlspecialchars($event["description"] ?? "")) ?>
                    </p>

                    <div class="ev-single-price mb-4">
                        <h3 class="m-0"><?= number_format((float)$event["price"], 2, ",", ".") ?>€</h3>
                        <span class="ev-muted">Bilhetes Disponíveis: <?= (int)$event["stock"] ?></span>
                    </div>

                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">

                        <?php if ((int)$event["stock"] <= 0): ?>

                            <button class="btn btn-secondary" disabled>
                                Sem stock
                            </button>

                        <?php elseif (($_SESSION["role"] ?? "") === "admin"): ?>

                            <button class="btn btn-outline-secondary" disabled
                                    title="Admins não podem comprar">
                                Apenas visualização
                            </button>

                        <?php else: ?>

                            <button class="btn btn-success ev-btn-buy"
                                    type="button"
                                    onclick="handleAddToCart(<?= (int)$event['id'] ?>)">
                                Adicionar ao carrinho <i class="fa-solid fa-cart-arrow-down"></i>
                            </button>

                        <?php endif; ?>

                        <a class="btn btn-outline-light ev-btn-back" href="events.php">
                            ← Voltar
                        </a>

                    </div>

                </div>
                
            </section>

        <?php endif; ?>
    </div>

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

</main>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
    <div id="cart-toast" class="toast align-items-center text-bg-success border-0"
        role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="cart-toast-message"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

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
