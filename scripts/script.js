/* --------------------------------------------------------------------
SEGURANÇA PHP
-------------------------------------------------------------------- */
const safeIsLoggedIn =
    typeof isLoggedIn !== "undefined" ? isLoggedIn : false;

const safeUserRole =
    typeof userRole !== "undefined" ? userRole : "guest";

/* --------------------------------------------------------------------
NOTIFICAÇÕES TOAST
-------------------------------------------------------------------- */
function showToast(message, type = 'primary') {
    // Tudo o que NÃO for erro aparece a verde
    if (type !== 'danger') type = 'success';

    const toastEl = document.getElementById('cart-toast');
    const toastMessage = document.getElementById('cart-toast-message');

    if (!toastEl || !toastMessage) return;

    // Mensagem
    toastMessage.textContent = message;

    // Mudar o tipo de toast
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;

    // Iniciar o toast
    const bsToast = new bootstrap.Toast(toastEl);
    bsToast.show();
}

/* --------------------------------------------------------------------
ADICIONAR AO CARRINHO
-------------------------------------------------------------------- */
function addToCart(eventId) {
    fetch("cart_action.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: `action=add&event_id=${eventId}&quantity=1`
    })
    .then(res => res.json())
    .then(data => {
        if (data.notification) {
            showToast(data.notification, data.type || "primary");
        } else {
            showToast("Adicionado ao carrinho!", "success");
        }
    })
    .catch(() => showToast("Erro ao adicionar ao carrinho.", "danger"));
}

function handleAddToCart(eventId) {

    if (!isLoggedIn) {
        alert("Precisas de fazer login para adicionar ao carrinho.");
        return;
    }

    if (userRole !== "user") {
        alert("Apenas utilizadores podem comprar bilhetes.");
        return;
    }

    addToCart(eventId);
}
