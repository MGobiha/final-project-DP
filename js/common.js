/**
 * AutoTrack Common JavaScript Functions
 */

/* =========================================================
   ESCAPE HTML
   Prevent HTML from database values being interpreted
   ========================================================= */

function escapeHtml(value) {
  if (value === null || value === undefined) {
    return "";
  }

  const div = document.createElement("div");

  div.textContent = String(value);

  return div.innerHTML;
}

/* =========================================================
   FORMAT TIME
   18:00:00 -> 06:00 PM
   ========================================================= */

function formatTime(time) {
  if (!time) {
    return "Not provided";
  }

  const parts = time.split(":");

  let hour = parseInt(parts[0], 10);

  const minute = parts[1];

  const period = hour >= 12 ? "PM" : "AM";

  hour = hour % 12 || 12;

  return String(hour).padStart(2, "0") + ":" + minute + " " + period;
}

/* =========================================================
   OPEN MODAL
   ========================================================= */

function openModal(modalId) {
  const modal = document.getElementById(modalId);

  if (!modal) {
    console.error("Modal not found:", modalId);

    return;
  }

  modal.classList.add("show");

  modal.setAttribute("aria-hidden", "false");

  document.body.classList.add("modal-open");
}

/* =========================================================
   CLOSE MODAL
   ========================================================= */

function closeModal(modalId) {
  const modal = document.getElementById(modalId);

  if (!modal) {
    return;
  }

  modal.classList.remove("show");

  modal.setAttribute("aria-hidden", "true");

  document.body.classList.remove("modal-open");
}

/* =========================================================
   SHOW LOADING
   ========================================================= */

function showLoading(elementId, message = "Loading...") {
  const element = document.getElementById(elementId);

  if (!element) {
    return;
  }

  element.innerHTML = `

        <div class="popup-loading">

            <div class="loading-spinner">
            </div>

            <p>
                ${escapeHtml(message)}
            </p>

        </div>

    `;
}

/* =========================================================
   SHOW ERROR
   ========================================================= */

function showError(elementId, message) {
  const element = document.getElementById(elementId);

  if (!element) {
    return;
  }

  element.innerHTML = `

        <div class="popup-error">

            <div class="popup-error-icon">
                ⚠️
            </div>

            <strong>
                Something went wrong
            </strong>

            <p>
                ${escapeHtml(message)}
            </p>

        </div>

    `;
}

/* =========================================================
   GET JSON FROM PHP
   Reusable AJAX helper
   ========================================================= */

async function fetchJson(url) {
  const response = await fetch(url, {
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  });

  if (!response.ok) {
    throw new Error("Server error: " + response.status);
  }

  const text = await response.text();

  let data;

  try {
    data = JSON.parse(text);
  } catch (error) {
    console.error("Invalid JSON:", text);

    throw new Error("Server returned invalid data.");
  }

  return data;
}

/* =========================================================
   INITIALIZE COMMON MODALS

   Any element with:
   data-modal-close="garageModal"

   can close that modal.
   ========================================================= */

document.addEventListener("click", function (event) {
  const closeElement = event.target.closest("[data-modal-close]");

  if (!closeElement) {
    return;
  }

  const modalId = closeElement.getAttribute("data-modal-close");

  closeModal(modalId);
});

/* =========================================================
   ESCAPE KEY CLOSE
   ========================================================= */

document.addEventListener("keydown", function (event) {
  if (event.key !== "Escape") {
    return;
  }

  document.querySelectorAll(".garage-modal.show").forEach((modal) => {
    closeModal(modal.id);
  });
});
