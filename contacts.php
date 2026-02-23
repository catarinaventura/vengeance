<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name    = trim($_POST["name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $phone   = trim($_POST["phone"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $surname === "" || $email === "" || $message === "") {
        $_SESSION["notification"] = "Preenche todos os campos obrigatórios`!";
        $_SESSION["notification_type"] = "danger";
        header("Location: contacts.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["notification"] = "Esse email não é válido.";
        $_SESSION["notification_type"] = "danger";
        header("Location: contacts.php");
        exit;
    }

    $_SESSION["notification"] = "Mensagem enviada com sucesso!";
    $_SESSION["notification_type"] = "success";
    header("Location: contacts.php");
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

        <title>Vengeance︱Contactos</title>

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

<img src="pictures/contacts.jpg" class="background" alt="Vengeance Background">

<!-- CONTEUDO PRINCIPAL -->

    <main>

        <section class="contact-section my-5">
            <div class="container contactos-container">

                <div class="text-center">
                    <h1>Entre em contacto</h1>
                    <p>Para qualquer questão ou problema relativo à sua compra ou aos eventos.</p>
                </div>

                <div class="row align-items-center justify-content-center">

                    <div class="col-md-12 mb-4">
                        <form method="POST" action="">

                        <div class="mb-3">
                            <label for="name" class="form-label">Nome:</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Insira o seu nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="surname" class="form-label">Apelido:</label>
                            <input type="text" class="form-control" id="surname" name="surname" placeholder="Insira o seu apelido" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Insira o seu email" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Telemóvel (Opcional):</label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Insira o seu numero de telemóvel" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Mensagem:</label>
                            <textarea class="form-control" id="message" rows="5" name="message" placeholder="Escreva a sua mensagem aqui..." required></textarea>
                        </div>

                        <div class="text-center" id="enviar-mensagem">
                            <button type="submit" class="btn btn-light" id="enviar-btn">Enviar</button>
                        </div>

                        </form>
                    </div>

                </div>

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