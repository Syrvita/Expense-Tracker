document.addEventListener("DOMContentLoaded", () => {
  // Category form validation
  const categoryForm = document.querySelector('form[action=""] input[name="name"]')?.form;
  if (categoryForm) {
    categoryForm.addEventListener("submit", (e) => {
      const name = categoryForm.querySelector('input[name="name"]').value.trim();
      if (name.length === 0) {
        e.preventDefault();
        alert("Category name is required.");
      }
    });
  }

  // Expense form validation
  const expenseForm = document.querySelector('form[action=""] input[name="title"]')?.form;
  if (expenseForm) {
    expenseForm.addEventListener("submit", (e) => {
      const title = expenseForm.querySelector('input[name="title"]')?.value.trim() || "";
      const categoryId = expenseForm.querySelector('select[name="category_id"]')?.value || "";
      const amount = expenseForm.querySelector('input[name="amount"]')?.value || "";
      const date = expenseForm.querySelector('input[name="expense_date"]')?.value || "";

      if (title.length === 0) {
        e.preventDefault();
        alert("Title is required.");
        return;
      }

      if (categoryId === "") {
        e.preventDefault();
        alert("Category is required.");
        return;
      }

      const amtNum = parseFloat(amount);
      if (!amount || isNaN(amtNum) || amtNum <= 0) {
        e.preventDefault();
        alert("Amount must be greater than 0.");
        return;
      }

      if (!date) {
        e.preventDefault();
        alert("Date is required.");
        return;
      }
    });
  }
});

// Render monthlyChart when present
document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById("monthlyChart");
  if (canvas) {
    try {
      const labels = JSON.parse(canvas.dataset.labels || "[]");
      const vals = JSON.parse(canvas.dataset.values || "[]");

      const ctx = canvas.getContext("2d");

      // Remove previous chart instance if any (safe-guard)
      if (window._monthlyChart instanceof Chart) {
        window._monthlyChart.destroy();
      }

      window._monthlyChart = new Chart(ctx, {
        type: "bar",
        data: {
          labels: labels,
          datasets: [{
            label: "Spent",
            data: vals,
            backgroundColor: labels.map((_, i) => {
              // muted palette; rotate through greens/browns
              const palette = [
                "rgba(32,86,71,0.86)",
                "rgba(120,96,72,0.86)",
                "rgba(129,142,130,0.86)",
                "rgba(140,120,101,0.86)",
                "rgba(101,80,70,0.86)"
              ];
              return palette[i % palette.length];
            }),
            borderRadius: 8,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { mode: 'index' }
          },
          scales: {
            x: {
              ticks: { color: "rgba(205,196,185,0.9)" }
            },
            y: {
              ticks: { color: "rgba(205,196,185,0.9)" },
              beginAtZero: true
            }
          }
        }
      });
    } catch (e) {
      console.error("Chart error", e);
    }
  }
});

