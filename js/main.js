function toggleSidebar() {
  document.querySelector(".sidebar")?.classList.toggle("open");
}

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

  const links = document.querySelectorAll(".nav-link");
  const currentPath = window.location.pathname.split("/").pop();
  links.forEach((link) => {
    const href = link.getAttribute("href");
    if (href && href === currentPath) {
      link.classList.add("active");
    }
  });
});

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

function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;
  const inputs = form.querySelectorAll("[required]");
  let valid = true;
  inputs.forEach((input) => {
    if (!String(input.value).trim()) {
      input.style.borderColor = "var(--danger)";
      valid = false;
    } else {
      input.style.borderColor = "";
    }
  });
  return valid;
}

function printPage() {
  window.print();
}