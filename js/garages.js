/**
 * Garage Functions
 */

/* =========================================================
   GARAGE BUTTON CLICK
   ========================================================= */

document.addEventListener("click", function (event) {
  const button = event.target.closest(".view-garage-btn");

  if (!button) {
    return;
  }

  const garageId = button.dataset.garageId;

  loadGarageDetails(garageId);
});

/* =========================================================
   LOAD GARAGE DETAILS
   ========================================================= */

async function loadGarageDetails(garageId) {
  openModal("garageModal");

  showLoading("garageModalBody", "Loading garage information...");

  try {
    const data = await fetchJson(
      "garage-details.php?id=" + encodeURIComponent(garageId),
    );

    if (!data.success) {
      showError("garageModalBody", data.message || "Unable to load garage.");

      return;
    }

    displayGarageDetails(data.garage, data.services);
  } catch (error) {
    console.error("Garage error:", error);

    showError("garageModalBody", error.message);
  }
}

/* =========================================================
   DISPLAY GARAGE
   ========================================================= */

function displayGarageDetails(garage, services) {
  const container = document.getElementById("garageModalBody");

  if (!container) {
    return;
  }

  const servicesHtml = createGarageServicesHtml(services);

  const mapHtml = createGarageMapHtml(garage);

  const directionsHtml = createDirectionsButton(garage);

  container.innerHTML = `

        <div class="garage-popup-grid">


            <div class="garage-popup-info">


                <div
                    class="garage-popup-name"
                >

                    <div
                        class="garage-popup-icon"
                    >
                        🔧
                    </div>


                    <div>

                        <h3>
                            ${escapeHtml(garage.garage_name)}
                        </h3>

                        <span>
                            ${escapeHtml(garage.district || "")}
                        </span>

                    </div>

                </div>


                <div
                    class="garage-info-list"
                >

                    ${createInfoItem(
                      "📍",
                      "Address",
                      garage.address || "Not provided",
                    )}


                    ${createInfoItem(
                      "📞",
                      "Mobile",
                      garage.mobile_number || "Not provided",
                    )}


                    ${createInfoItem(
                      "✉️",
                      "Email",
                      garage.email || "Not provided",
                    )}


                    ${createInfoItem(
                      "🕒",
                      "Opening Hours",
                      createOpeningHours(garage),
                    )}

                </div>


                ${
                  garage.description
                    ? `

                    <div
                        class="garage-description-box"
                    >

                        <h4>
                            About Garage
                        </h4>

                        <p>
                            ${escapeHtml(garage.description)}
                        </p>

                    </div>

                    `
                    : ""
                }


            </div>


            <div>

                ${mapHtml}

            </div>


        </div>


        <div class="popup-services">

            <div
                class="popup-section-title"
            >

                <h3>
                    Services Offered
                </h3>

                <span>
                    ${services.length}
                    service${services.length === 1 ? "" : "s"}
                </span>

            </div>


            ${servicesHtml}

        </div>


        <div
            class="garage-popup-actions"
        >

            ${directionsHtml}


            <a
                href="login.php?garage_id=${garage.garage_id}"
                class="btn btn-primary"
            >
                📅 Book Service
            </a>

        </div>

    `;
}

/* =========================================================
   INFO ITEM
   ========================================================= */

function createInfoItem(icon, label, value) {
  return `

        <div>

            <span>
                ${icon}
                ${escapeHtml(label)}
            </span>

            <strong>
                ${escapeHtml(value)}
            </strong>

        </div>

    `;
}

/* =========================================================
   OPENING HOURS
   ========================================================= */

function createOpeningHours(garage) {
  if (!garage.opening_time || !garage.closing_time) {
    return "Not provided";
  }

  return (
    formatTime(garage.opening_time) + " - " + formatTime(garage.closing_time)
  );
}

/* =========================================================
   GARAGE SERVICES
   ========================================================= */

function createGarageServicesHtml(services) {
  if (!services || services.length === 0) {
    return `

            <div class="empty-services">

                No service information
                has been added yet.

            </div>

        `;
  }

  return services
    .map((service) => {
      let priceHtml = "";

      if (service.estimated_price) {
        priceHtml = `

                        <span
                            class="service-price"
                        >
                            Rs.
                            ${Number(service.estimated_price).toLocaleString()}
                        </span>

                    `;
      }

      return `

                    <div
                        class="popup-service-item"
                    >

                        <div>

                            <strong>
                                ${escapeHtml(service.service_name)}
                            </strong>


                            ${
                              service.description
                                ? `

                                <p>
                                    ${escapeHtml(service.description)}
                                </p>

                                `
                                : ""
                            }

                        </div>


                        ${priceHtml}


                    </div>

                `;
    })
    .join("");
}

/* =========================================================
   MAP
   ========================================================= */

function createGarageMapHtml(garage) {
  if (!garage.latitude || !garage.longitude) {
    return `

            <div class="map-unavailable">

                📍

                <p>
                    Location has not been
                    added for this garage.
                </p>

            </div>

        `;
  }

  const latitude = encodeURIComponent(garage.latitude);

  const longitude = encodeURIComponent(garage.longitude);

  return `

        <iframe
            class="garage-map"

            src="https://www.google.com/maps?q=${latitude},${longitude}&z=15&output=embed"

            loading="lazy"

            title="Garage location"

            referrerpolicy="
                no-referrer-when-downgrade
            "
        ></iframe>

    `;
}

/* =========================================================
   DIRECTIONS BUTTON
   ========================================================= */

function createDirectionsButton(garage) {
  if (!garage.latitude || !garage.longitude) {
    return "";
  }

  const latitude = encodeURIComponent(garage.latitude);

  const longitude = encodeURIComponent(garage.longitude);

  return `

        <a

            href="https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}"

            target="_blank"

            rel="noopener"

            class="btn btn-secondary"

        >
            📍 Get Directions
        </a>

    `;
}
