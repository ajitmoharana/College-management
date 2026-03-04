// js/main.js ✅ UPDATED (sidebar toggle + alerts + ACTIVE link + unified confirmDelete POST+CSRF)
function toggleSidebar() {
  document.querySelector(".sidebar")?.classList.toggle("open");
}

// ===== AUTO DISMISS ALERTS + ACTIVE LINK =====
document.addEventListener("DOMContentLoaded", () => {
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      alert.style.transform = "translateY(-10px)";
      alert.style.transition = "all 0.4s";
      setTimeout(() => alert.remove(), 400);
    }, 4000);
  });

  // Highlight active nav link (optional)
  const links = document.querySelectorAll(".nav-link");
  const current = window.location.pathname;
  links.forEach((link) => {
    const href = link.getAttribute("href");
    if (href && current.includes(href)) link.classList.add("active");
  });
});

// ===== CONFIRM DELETE (POST + CSRF) =====
// Usage in PHP:
// <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
// <a href="#" onclick="confirmDelete(ID,'file.php')" ...>🗑</a>
function confirmDelete(id, endpoint) {
  if (!confirm("Are you sure you want to delete this record? This action cannot be undone.")) return;

  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  const form = document.createElement("form");
  form.method = "POST";
  form.action = endpoint;

  const csrf = document.createElement("input");
  csrf.type = "hidden";
  csrf.name = "csrf_token";
  csrf.value = token;

  const del = document.createElement("input");
  del.type = "hidden";
  del.name = "delete_id";
  del.value = id;

  form.appendChild(csrf);
  form.appendChild(del);
  document.body.appendChild(form);
  form.submit();
}

// ===== SEARCH TABLE =====
function searchTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll("#" + tableId + " tbody tr");
  rows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
}

// ===== FORM VALIDATION =====
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;
  const inputs = form.querySelectorAll("[required]");
  let valid = true;
  inputs.forEach((input) => {
    if (!input.value.trim()) {
      input.style.borderColor = "var(--danger)";
      valid = false;
    } else {
      input.style.borderColor = "";
    }
  });
  return valid;
}

// ===== PRINT PAGE =====
function printPage() {
  window.print();
}