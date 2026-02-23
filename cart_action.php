<?php
include "db.php";

/* --------------------------------------------------------------------
SEGURANÇA: Apenas utilizadores com role "user" podem usar o carrinho
-------------------------------------------------------------------- */
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "user") {
    respond("users/login.php", "Precisas de fazer login para usar o carrinho.", "warning");
}

if (($_SESSION["role"] ?? "") === "admin") {
    respond("events.php", "Admins não podem adicionar eventos ao carrinho.", "warning");
}

/* --------------------------------------------------------------------
NOTIFICAÇÕES
-------------------------------------------------------------------- */
function isAjaxRequest(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function respond($redirectUrl, $message, $type = "primary", $extra = []) {
    // Tudo o que NÃO for erro aparece a verde
    if ($type !== "danger") { $type = "success"; }

    if (isAjaxRequest()) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array_merge([
            "notification" => $message,
            "type" => $type
        ], $extra));
        exit;
    }

    $_SESSION["notification"] = $message;
    $_SESSION["notification_type"] = $type;
    header("Location: " . $redirectUrl);
    exit;
}

/* --------------------------------------------------------------------
HELPERS
-------------------------------------------------------------------- */
function getEvent($conn, $event_id) {
    $stmt = $conn->prepare("
        SELECT id, name, price, stock, is_active
        FROM events
        WHERE id = ? AND is_active = 1
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc();
}

function cartCount() {
    return array_sum($_SESSION["cart"] ?? []);
}

$action   = $_REQUEST["action"] ?? "";
$event_id = isset($_REQUEST["event_id"]) ? (int)$_REQUEST["event_id"] : 0;
$quantity = isset($_REQUEST["quantity"]) ? (int)$_REQUEST["quantity"] : 1;

if (!isset($_SESSION["cart"])) $_SESSION["cart"] = [];

/* --------------------------------------------------------------------
AÇÕES: add, update, remove, checkout
-------------------------------------------------------------------- */
switch ($action) {

    case "add": {
        if (!$event_id) {
            respond("events.php", "Evento inválido.", "danger");
        }

        $event = getEvent($conn, $event_id);
        if (!$event) {
            respond("events.php", "Evento inválido ou indisponível.", "danger");
        }

        $stock = (int)$event["stock"];
        if ($stock <= 0) {
            respond("event.php?id=" . $event_id, "Sem stock para este evento.", "warning");
        }

        $current = (int)($_SESSION["cart"][$event_id] ?? 0);
        $newQty  = $current + 1;

        if ($newQty > $stock) {
            $_SESSION["cart"][$event_id] = $stock;
            respond(
                "event.php?id=" . $event_id,
                "Só existem {$stock} bilhetes disponíveis! Quantidade ajustada.",
                "warning",
                ["cart_total_items" => cartCount()]
            );
        }

        $_SESSION["cart"][$event_id] = $newQty;

        respond(
            "event.php?id=" . $event_id,
            "Adicionado ao carrinho!",
            "success",
            ["cart_total_items" => cartCount()]
        );
        break;
    }

    case "update": {
        if (!$event_id) {
            respond("cart.php", "Evento inválido.", "danger");
        }

        $event = getEvent($conn, $event_id);
        if (!$event) {
            unset($_SESSION["cart"][$event_id]);
            respond("cart.php", "Evento já não está disponível, removido do carrinho.", "warning", ["cart_total_items" => cartCount()]);
        }

        $stock = (int)$event["stock"];

        if ($quantity <= 0) {
            unset($_SESSION["cart"][$event_id]);
            respond("cart.php", "Item removido do carrinho.", "info", ["cart_total_items" => cartCount()]);
        }

        if ($quantity > $stock) {
            $_SESSION["cart"][$event_id] = $stock;
            respond("cart.php", "Só existem {$stock} bilhetes disponíveis! Quantidade ajustada.", "warning", ["cart_total_items" => cartCount()]);
        }

        $_SESSION["cart"][$event_id] = $quantity;
        respond("cart.php", "Quantidade atualizada.", "success", ["cart_total_items" => cartCount()]);
        break;
    }

    case "remove": {
        if ($event_id && isset($_SESSION["cart"][$event_id])) {
            unset($_SESSION["cart"][$event_id]);
        }

        respond("cart.php", "Item removido do carrinho.", "info", ["cart_total_items" => cartCount()]);
        break;
    }


    case "checkout": {
        if (empty($_SESSION["cart"])) {
            $_SESSION["notification"] = "O seu carrinho está vazio.";
            $_SESSION["notification_type"] = "warning";
            header("Location: cart.php");
            exit;
        }

        $user_id = (int)$_SESSION["user_id"];

        $conn->begin_transaction();
        try {
            // VALIDAR STOCK
            $total_amount = 0.0;
            $lineItems = [];

            foreach ($_SESSION["cart"] as $eid => $qty) {
                $eid = (int)$eid;
                $qty = (int)$qty;

                $stmt = $conn->prepare("
                    SELECT id, price, stock, is_active
                    FROM events
                    WHERE id = ? AND is_active = 1
                    FOR UPDATE
                ");

                $stmt->bind_param("i", $eid);
                $stmt->execute();
                $res = $stmt->get_result();
                $event = $res->fetch_assoc();

                if (!$event) {
                    throw new Exception("Um ou mais eventos já não estão disponíveis.");
                }

                $stock = (int)$event["stock"];
                if ($stock < $qty) {
                    throw new Exception("Stock insuficiente para um ou mais eventos.");
                }

                $unit = (float)$event["price"];
                $subtotal = $unit * $qty;

                $total_amount += $subtotal;
                $lineItems[$eid] = ["qty" => $qty, "unit" => $unit, "subtotal" => $subtotal];
            }

            $stmt = $conn->prepare("INSERT INTO purchases (user_id, total_amount) VALUES (?, ?)");
            $stmt->bind_param("id", $user_id, $total_amount);
            $stmt->execute();
            $purchase_id = $stmt->insert_id;

            $stmtItem = $conn->prepare("
                INSERT INTO purchase_items (purchase_id, event_id, quantity, price_at_purchase)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($lineItems as $eid => $data) {
                $q = (int)$data["qty"];
                $subtotal = (float)$data["subtotal"]; // unit * qty

                $stmtItem->bind_param("iiid", $purchase_id, $eid, $q, $subtotal);
                $stmtItem->execute();
            }

            $stmtStock = $conn->prepare("UPDATE events SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($lineItems as $eid => $data) {
                $q = (int)$data["qty"];
                $stmtStock->bind_param("iii", $q, $eid, $q);
                $stmtStock->execute();
                if ($stmtStock->affected_rows !== 1) {
                    throw new Exception("Erro ao atualizar stock.");
                }
            }

            $conn->commit();

            $_SESSION["cart"] = [];
            $_SESSION["notification"] = "Compra criada com sucesso! (Estado: pending)";
            $_SESSION["notification_type"] = "success";
            header("Location: users/profile.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION["notification"] = $e->getMessage();
            $_SESSION["notification_type"] = "danger";
            header("Location: cart.php");
            exit;
        }
    }
}

header("Location: cart.php");
exit;
?>
