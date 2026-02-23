<?php
include 'db.php';

/* --------------------------------------------------------------------
CARREGAR EVENTS
-------------------------------------------------------------------- */
$newestEvents = $conn->query("
    SELECT id, name, date, image, price
    FROM events
    WHERE is_active = 1
    ORDER BY id DESC
    LIMIT 3
");

$upcomingEvents = $conn->query("
    SELECT id, name, date, image, price
    FROM events
    WHERE is_active = 1
      AND date >= NOW()
    ORDER BY date ASC
    LIMIT 3
");


?>

<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="description" content="Bem-vindo à Vengance, onde podes comprar bilhetes para os maiores gaming events de Portugal Continental!">
        <meta name="author" content="Catarina Ventura">
        <meta name="keywords" content="Vengeance, Gaming, Events, Torneios, E-Sports, Portugal, Bilhetes">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Vengeance︱Home</title>

        <script src="https://kit.fontawesome.com/e815cc27bb.js" crossorigin="anonymous"></script>
        <script src="scripts/script.js"></script>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="styles/main_style.css">
        <link rel="icon" type="image/png" href="pictures/logo.png" alt="Vengeance Logo">

    </head>

    <body>

<!-- CABEÇALHO -->

<?php include "header.php"; ?>

<!-- CONTEUDO PRINCIPAL -->

        <img src="pictures/home.jpg" class="background" alt="Vengeance Background">

        <main>

            <section class="intro text-center my-5 section-titulo">
                <h1>Bem-vindo à Vengeance!</h1>
                <p>O destino para os maiores Gaming e E-Sport Events de Portugal Continental!</p>
            </section>
            
            <section class="container my-5 text-center section-content" id="diff">

            <h4 class="highlights_with_link"><a href="users/register.php" class="links">Registe-se</a> e reserve a sua presença num dos nossos eventos!</h4>

            </section>

            <section class="container my-5 home-events section-content">

            <h2 class="section-title mb-4 text-center">Próximos Eventos</h2>

                <div class="row g-4 home-events-row">
                    <?php while ($event = $upcomingEvents->fetch_assoc()): ?>
                        <?php
                            $img = $event["image"] ? str_replace("../", "", $event["image"]) : "pictures/events.jpg";
                            $dateFormatted = date("d/m/Y H:i", strtotime($event["date"]));
                        ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars($event["name"]) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($event["name"]) ?></h5>
                                    <p class="card-text"><?= $dateFormatted ?></p>
                                </div>
                                <div class="card-footer row justify-content-between align-items-center">
                                    <strong><?= number_format((float)$event["price"], 2, ",", ".") ?>€</strong>
                                    <a class="btn btn-sm botao-geral" href="event.php?id=<?= (int)$event["id"] ?>">
                                        Ver Detalhes →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <p class="text-center highlights_with_link">Explore todos os eventos disponíveis <a href="events.php" class="links">aqui</a>.</p>

            </section>

            <section class="container my-5 home-events section-content">

                <h2 class="section-title mb-4 text-center">Adicionados Recentemente</h2>

                <div class="row g-4 home-events-row">
                    <?php while ($event = $newestEvents->fetch_assoc()): ?>
                        <?php
                            $img = $event["image"] ? str_replace("../", "", $event["image"]) : "pictures/events.jpg";
                            $dateFormatted = date("d/m/Y H:i", strtotime($event["date"]));
                        ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars($event["name"]) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($event["name"]) ?></h5>
                                    <p class="card-text"><?= $dateFormatted ?></p>
                                </div>
                                <div class="card-footer row justify-content-between align-items-center">
                                    <strong><?= number_format((float)$event["price"], 2, ",", ".") ?>€</strong>
                                    <a class="btn btn-sm botao-geral" href="event.php?id=<?= (int)$event["id"] ?>">
                                        Ver Detalhes →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <p class="text-center highlights_with_link">Explore todos os eventos disponíveis <a href="events.php" class="links">aqui</a>.</p>

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