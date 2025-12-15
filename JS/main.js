document.addEventListener("DOMContentLoaded", () => {
  // Helper: Show Toast Notification (Simulating a library like SweetAlert or Toastify)
  window.showToast = (message, type = "success") => {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.innerText = message;
    toast.style.position = "fixed";
    toast.style.bottom = "20px";
    toast.style.right = "20px";
    toast.style.padding = "15px 25px";
    toast.style.background = type === "success" ? "#28a745" : "#dc3545";
    toast.style.color = "#fff";
    toast.style.borderRadius = "5px";
    toast.style.zIndex = "1000";
    toast.style.boxShadow = "0 4px 6px rgba(0,0,0,0.1)";

    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  };

  // Global: Async Form Submission (Generic Handler)
  const ajaxForms = document.querySelectorAll(".ajax-form");
  ajaxForms.forEach((form) => {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(form);
      const action = form.getAttribute("action") || window.location.href;

      try {
        const response = await fetch(action, {
          method: "POST",
          body: formData,
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });

        const result = await response.json();

        if (result.success) {
          showToast(result.message, "success");
          if (result.redirect) setTimeout(() => (window.location.href = result.redirect), 1000);
          if (result.reload) setTimeout(() => window.location.reload(), 1000);
        } else {
          showToast(result.error || "An error occurred", "error");
        }
      } catch (err) {
        console.error(err);
        showToast("Server connection error.", "error");
      }
    });
  });
});
