document.addEventListener("DOMContentLoaded", function () {
  const chatForm = document.getElementById("chatForm");
  const chatInput = document.getElementById("chatMessage");
  const messages = document.getElementById("chatMessages");

  if (!chatForm || !chatInput || !messages) {
    console.error("Chat elements not found.");
    return;
  }

  chatForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const message = chatInput.value.trim();

    if (!message) {
      return;
    }

    // Show user message
    addMessage(message, "user");

    chatInput.value = "";
    chatInput.focus();

    // Loading message
    const loadingMessage = document.createElement("div");
    loadingMessage.className = "message bot";
    loadingMessage.textContent = "Checking your vehicle problem...";
    messages.appendChild(loadingMessage);

    scrollToBottom();

    try {
      const response = await fetch("api/chatbot.php", {
        method: "POST",

        headers: {
          "Content-Type": "application/json",
        },

        body: JSON.stringify({
          message: message,
        }),
      });

      if (!response.ok) {
        throw new Error("Server returned status " + response.status);
      }

      const data = await response.json();

      loadingMessage.remove();

      if (data.success) {
        addBotResponse(data);
      } else {
        addMessage(data.message || "I couldn't identify the problem.", "bot");
      }
    } catch (error) {
      console.error(error);

      loadingMessage.remove();

      addMessage("Sorry, I couldn't connect to the diagnostic service.", "bot");
    }
  });

  function addMessage(text, type) {
    const div = document.createElement("div");

    div.className = "message " + type;

    div.textContent = text;

    messages.appendChild(div);

    scrollToBottom();
  }

  function addBotResponse(data) {
    const div = document.createElement("div");

    div.className = "message bot diagnostic-result";

    let html = "";

    // Normal response
    if (data.reply) {
      html += `
                <div class="diagnostic-intro">
                    ${escapeHtml(data.reply)}
                </div>
            `;
    }

    // Vehicle
    if (data.vehicle) {
      html += `
                <div class="diagnostic-section">
                    <strong>🚗 Vehicle</strong><br>
                    ${escapeHtml(data.vehicle)}
                </div>
            `;
    }

    // Detected problem
    if (data.problem) {
      html += `
                <div class="diagnostic-section">
                    <strong>⚠️ Possible Problem</strong><br>
                    ${escapeHtml(data.problem)}
                </div>
            `;
    }

    // Parts involved
    if (data.parts_involved) {
      html += `
                <div class="diagnostic-section">
                    <strong>🔧 Parts Involved</strong><br>
                    ${escapeHtml(data.parts_involved)}
                </div>
            `;
    }

    // Parts/checks
    if (data.parts_to_check) {
      html += `
                <div class="diagnostic-section">
                    <strong>🔍 Recommended Checks</strong><br>
                    ${escapeHtml(data.parts_to_check)}
                </div>
            `;
    }

    // Recommendation
    if (data.recommendation) {
      html += `
                <div class="diagnostic-section">
                    <strong>💡 Recommendation</strong><br>
                    ${escapeHtml(data.recommendation)}
                </div>
            `;
    }

    div.innerHTML = html;

    messages.appendChild(div);

    scrollToBottom();
  }

  function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
  }

  function escapeHtml(text) {
    const div = document.createElement("div");

    div.textContent = text;

    return div.innerHTML;
  }
});
