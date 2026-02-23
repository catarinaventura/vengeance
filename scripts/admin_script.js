document.addEventListener("DOMContentLoaded", () => {

  /* --------------------------------------------------------------------
  HELPERS
  -------------------------------------------------------------------- */
  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function toDatetimeLocal(value) {
    if (!value) return "";
    if (value.includes("T")) return value;

    const v = value.replace(" ", "T");
    return v.length >= 16 ? v.substring(0, 16) : v;
  }

  /* --------------------------------------------------------------------
  EDITAR USERS
  -------------------------------------------------------------------- */
  document.querySelectorAll(".edit-user-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");

      const usernameCell = row.querySelector(".username");
      const emailCell = row.querySelector(".email");

      const username = usernameCell.textContent.trim();
      const email = emailCell.textContent.trim();

      usernameCell.innerHTML = `<input type="text" class="form-control form-control-sm" value="${escapeHtml(username)}">`;
      emailCell.innerHTML = `<input type="email" class="form-control form-control-sm" value="${escapeHtml(email)}">`;

      row.querySelector(".edit-user-btn").style.display = "none";
      row.querySelector(".save-user-btn").style.display = "inline-block";
    });
  });

  document.querySelectorAll(".save-user-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const row = btn.closest("tr");

      const newUsername = row.querySelector(".username input").value.trim();

      const emailInput = row.querySelector(".email input");
      const newEmail = emailInput.value.trim();

      // validação de email (HTML5)
      if (!emailInput.checkValidity()) {
        emailInput.reportValidity();
        return;
      }

      const form = document.createElement("form");
      form.method = "POST";
      form.style.display = "none";

      form.innerHTML = `
        <input type="hidden" name="edit_id" value="${id}">
        <input type="hidden" name="username[${id}]" value="${escapeHtml(newUsername)}">
        <input type="hidden" name="email[${id}]" value="${escapeHtml(newEmail)}">
      `;

      document.body.appendChild(form);
      form.submit();
    });
  });

  /* --------------------------------------------------------------------
  PESQUISA NO HISTÓRICO DE COMPRAS
  -------------------------------------------------------------------- */
  const purchaseSearch = document.getElementById("purchaseSearch");
  if (purchaseSearch) {
    purchaseSearch.addEventListener("input", () => {
      const q = purchaseSearch.value.toLowerCase().trim();
      document.querySelectorAll("#purchases tbody tr").forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = text.includes(q) ? "" : "none";
      });
    });
  }

  /* --------------------------------------------------------------------
  EDITAR EVENTOS (SEM DESCRIÇÃO)
  Tabela: ID(0), Nome(1), Categoria(2), Data(3), Preço(4), Stock(5), Ações(6)
  -------------------------------------------------------------------- */
  document.querySelectorAll(".edit-event-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const row = btn.closest("tr");

      const name = row.cells[1].textContent.trim();
      const date = row.cells[3].textContent.trim();
      const price = row.cells[4].textContent.replace("€", "").trim();
      const stock = row.cells[5].textContent.trim();

      const currentCategoryId = row.dataset.categoryId || "";

      // Nome editable
      row.cells[1].innerHTML = `<input type="text" name="name[${id}]" value="${escapeHtml(name)}" class="form-control">`;

      // Categoria select editable
      const options = (window.CATEGORIES || []).map(c => {
        const selected = String(c.id) === String(currentCategoryId) ? "selected" : "";
        return `<option value="${c.id}" ${selected}>${escapeHtml(c.name)}</option>`;
      }).join("");

      row.cells[2].innerHTML = `
        <select name="category_id[${id}]" class="form-control">
          ${options}
        </select>
      `;

      // Data / Preço / Stock editable
      row.cells[3].innerHTML = `<input type="datetime-local" name="date[${id}]" value="${toDatetimeLocal(date)}" class="form-control">`;
      row.cells[4].innerHTML = `<input type="number" step="0.01" name="price[${id}]" value="${escapeHtml(price)}" class="form-control">`;
      row.cells[5].innerHTML = `<input type="number" name="stock[${id}]" value="${escapeHtml(stock)}" class="form-control">`;

      // trocar botão
      btn.outerHTML = `<button type="button" class="btn btn-success btn-sm save-event-btn" data-id="${id}">Guardar</button>`;

      row.querySelector(".save-event-btn").addEventListener("click", () => {
        const form = document.createElement("form");
        form.method = "POST";
        form.style.display = "none";
        form.action = "users/admin.php"; // garante que bate certo com o teu path

        form.innerHTML = `
          <input type="hidden" name="event_action" value="edit_event">
          <input type="hidden" name="edit_event_id" value="${id}">
          <input type="hidden" name="name[${id}]" value="${escapeHtml(row.querySelector('input[name^="name"]').value)}">
          <input type="hidden" name="category_id[${id}]" value="${escapeHtml(row.querySelector('select[name^="category_id"]').value)}">
          <input type="hidden" name="date[${id}]" value="${escapeHtml(row.querySelector('input[name^="date"]').value)}">
          <input type="hidden" name="price[${id}]" value="${escapeHtml(row.querySelector('input[name^="price"]').value)}">
          <input type="hidden" name="stock[${id}]" value="${escapeHtml(row.querySelector('input[name^="stock"]').value)}">
        `;

        document.body.appendChild(form);
        form.submit();
      });
    });
  });

});
