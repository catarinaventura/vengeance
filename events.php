<?php
include 'db.php';

/* --------------------------------------------------------------------
FILTROS QUERY
-------------------------------------------------------------------- */
$search = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);

$cats = [];
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while ($row = $catRes->fetch_assoc()) {
    $cats[] = $row;
}

$sql = "
    SELECT e.id, e.name, e.date, e.description, e.image, e.price, e.stock, e.is_active,
           c.name AS category_name
    FROM events e
    JOIN categories c ON e.category_id = c.id
    WHERE e.is_active = 1
";

$params = [];
$types = "";

/* --------------------------------------------------------------------
SEARCH BAR
-------------------------------------------------------------------- */
if ($search !== '') {
    $sql .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

/* --------------------------------------------------------------------
FILTROS DE CATEGORIA
-------------------------------------------------------------------- */
if ($category > 0) {
    $sql .= " AND e.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

$sql .= " ORDER BY e.date ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$eventsResult = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Bem-vindo à Vengance, onde podes comprar bilhetes para os maiores gaming events de Portugal Continental!">
    <meta name="author" content="Catarina Ventura">
    <meta name="keywords" content="Vengeance, Gaming, Events, Torneios, E-Sports, Portugal, Bilhetes">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vengeance︱Eventos</title>

    <script src="https://kit.fontawesome.com/e815cc27bb.js" crossorigin="anonymous"></script>
    <script src="scripts/script.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="styles/main_style.css">
    <link rel="stylesheet" href="styles/events_style.css">
    <link rel="icon" type="image/png" href="pictures/logo.png" alt="Vengeance Logo">
</head>

<body>

<?php include "header.php"; ?>

<img src="pictures/events.jpg" class="background" alt="Vengeance Background">

<main class="ev-page ev-list">

    <section class="intro text-center my-5 section-titulo">
        <h1 class="mb-4">Eventos</h1>
        <p>Explore os nossos eventos de gaming e E-Sports e garanta já o seu bilhete para a próxima torneio!</p>
    </section>

    <section class="container ev-wrap section-content">

        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center mb-4">
            <input type="hidden" name="category" value="<?= (int)$category ?>">

            <input
                type="text"
                name="search"
                class="form-control"
                style="max-width: 260px;"
                placeholder="Pesquisar eventos…"
                value="<?= htmlspecialchars($search) ?>"
            >

            <button class="btn botao-geral" type="submit">Pesquisar</button>

            <?php if ($search !== '' || $category > 0): ?>
                <a class="btn btn-outline-secondary" href="events.php">Limpar</a>
            <?php endif; ?>
        </form>

        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center mb-4">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

            <select name="category" class="form-select" style="max-width: 260px;">
                <option value="0">Todas as categorias</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($category === (int)$c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn botao-geral" type="submit">Filtrar</button>

            <?php if ($search !== '' || $category > 0): ?>
                <a class="btn btn-outline-secondary" href="events.php">Limpar</a>
            <?php endif; ?>
        </form>

        <div class="row g-4 ev-grid">

            <?php if ($eventsResult && $eventsResult->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Nenhum evento encontrado.
                    </div>
                </div>
            <?php endif; ?>

            <?php while ($e = $eventsResult->fetch_assoc()): ?>
                <?php
                    $img = $e["image"] ? str_replace("../", "", $e["image"]) : "pictures/events.jpg";
                    $dateFormatted = date("d/m/Y H:i", strtotime($e["date"]));
                ?>

                <div class="col-md-4 d-flex justify-content-center">
                    <article class="ev-card">

                        <img src="<?= htmlspecialchars($img) ?>" class="ev-card__img" alt="<?= htmlspecialchars($e["name"]) ?>">

                        <div class="ev-card__body">
                            <h5 class="ev-card__title"><?= htmlspecialchars($e["name"]) ?></h5>

                            <div class="ev-card__meta">
                                <span class="ev-pill"><?= htmlspecialchars($e["category_name"]) ?></span>
                                <span class="ev-date"><?= $dateFormatted ?></span>
                            </div>

                        </div>

                        <div class="ev-card__footer">
                            <strong class="ev-price"><?= number_format((float)$e["price"], 2, ",", ".") ?>€</strong>
                            <a class="btn btn-sm botao-geral" href="event.php?id=<?= (int)$e["id"] ?>">
                                Ver Detalhes →
                            </a>
                        </div>

                    </article>
                </div>
            <?php endwhile; ?>

        </div>
    </section>

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
