<?php

require_once 'config/database.php';

header('Content-Type: application/json');

$garageId =
    isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($garageId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid garage."
    ]);

    exit();
}


// =====================================================
// GET GARAGE
// =====================================================

$sql = "
    SELECT
        garage_id,
        garage_name,
        owner_name,
        email,
        mobile_number,
        telephone,
        address,
        city,
        district,
        latitude,
        longitude,
        opening_time,
        closing_time,
        description,
        image

    FROM garages

    WHERE garage_id = ?

    AND approval_status = 'approved'

    AND active_status = 1

    LIMIT 1
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database query failed."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$garage =
    mysqli_fetch_assoc($result);


if (!$garage) {

    echo json_encode([
        "success" => false,
        "message" => "Garage not found."
    ]);

    exit();
}


// =====================================================
// GET SERVICES
// =====================================================

$services = [];

$serviceSql = "
    SELECT
        service_name,
        description,
        estimated_price,
        estimated_duration_minutes

    FROM garage_services

    WHERE garage_id = ?

    AND active_status = 1

    ORDER BY service_name
";

$serviceStmt =
    mysqli_prepare(
        $conn,
        $serviceSql
    );

if ($serviceStmt) {

    mysqli_stmt_bind_param(
        $serviceStmt,
        "i",
        $garageId
    );

    mysqli_stmt_execute(
        $serviceStmt
    );

    $serviceResult =
        mysqli_stmt_get_result(
            $serviceStmt
        );

    while (
        $service =
        mysqli_fetch_assoc(
            $serviceResult
        )
    ) {

        $services[] = $service;
    }
}


// =====================================================
// RETURN JSON
// =====================================================

echo json_encode([
    "success" => true,
    "garage" => $garage,
    "services" => $services
]);

exit();
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
   
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
<h1>
    <?php
    echo htmlspecialchars(
        $garage["garage_name"]
    );
    ?>
</h1>

<p>
    <?php
    echo htmlspecialchars(
        $garage["address"]
    );
    ?>
</p>

<p>
    Mobile:
    <?php
    echo htmlspecialchars(
        $garage["mobile_number"]
    );
    ?>
</p>

<p>
    <?php
    echo htmlspecialchars(
        $garage["description"]
    );
    ?>
</p>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const modal =
            document.getElementById(
                "garageModal"
            );

        const modalBody =
            document.getElementById(
                "garageModalBody"
            );

        const closeButton =
            document.getElementById(
                "closeGarageModal"
            );

        const overlay =
            modal.querySelector(
                ".garage-modal-overlay"
            );


        // ============================================
        // OPEN GARAGE DETAILS
        // ============================================

        document
            .querySelectorAll(
                ".view-garage-btn"
            )
            .forEach(button => {

                button.addEventListener(
                    "click",
                    async function () {

                        const garageId =
                            this.dataset
                                .garageId;

                        modal.classList.add(
                            "show"
                        );

                        modal.setAttribute(
                            "aria-hidden",
                            "false"
                        );

                        document.body
                            .classList.add(
                                "modal-open"
                            );

                        modalBody.innerHTML =
                            `
                            <div class="garage-loading">
                                Loading garage information...
                            </div>
                            `;

                        try {

                            const response =
                                await fetch(
                                    "garage-details.php?id="
                                    + encodeURIComponent(
                                        garageId
                                    )
                                );

                            const data =
                                await response.json();


                            if (!data.success) {

                                modalBody.innerHTML =
                                    `
                                    <div class="garage-error">
                                        ${escapeHtml(
                                            data.message
                                        )}
                                    </div>
                                    `;

                                return;
                            }


                            showGarageDetails(
                                data.garage,
                                data.services
                            );

                        } catch (error) {

                            console.error(
                                error
                            );

                            modalBody.innerHTML =
                                `
                                <div class="garage-error">
                                    Unable to load garage details.
                                </div>
                                `;
                        }

                    }
                );

            });


        // ============================================
        // DISPLAY DETAILS
        // ============================================

        function showGarageDetails(
            garage,
            services
        ) {

            let servicesHtml = "";


            if (services.length > 0) {

                servicesHtml =
                    services.map(
                        service => {

                            let price = "";

                            if (
                                service
                                    .estimated_price
                            ) {

                                price =
                                    `
                                    <span
                                        class="service-price"
                                    >
                                        Rs.
                                        ${Number(
                                            service
                                                .estimated_price
                                        ).toLocaleString()}
                                    </span>
                                    `;
                            }


                            return `
                                <div
                                    class="popup-service-item"
                                >

                                    <div>

                                        <strong>
                                            ${escapeHtml(
                                                service
                                                    .service_name
                                            )}
                                        </strong>

                                        ${
                                            service
                                                .description
                                                ?
                                            `
                                            <p>
                                                ${escapeHtml(
                                                    service
                                                        .description
                                                )}
                                            </p>
                                            `
                                            :
                                            ""
                                        }

                                    </div>

                                    ${price}

                                </div>
                            `;
                        }
                    )
                    .join("");

            } else {

                servicesHtml =
                    `
                    <p class="muted">
                        No service information
                        has been added yet.
                    </p>
                    `;
            }


            let hours = "Not provided";

            if (
                garage.opening_time
                &&
                garage.closing_time
            ) {

                hours =
                    formatTime(
                        garage.opening_time
                    )
                    +
                    " - "
                    +
                    formatTime(
                        garage.closing_time
                    );
            }


            // ========================================
            // MAP
            // ========================================

            let mapHtml =
                `
                <div class="map-unavailable">
                    Location has not been
                    added for this garage.
                </div>
                `;

            let directionsButton = "";


            if (
                garage.latitude
                &&
                garage.longitude
            ) {

                const latitude =
                    encodeURIComponent(
                        garage.latitude
                    );

                const longitude =
                    encodeURIComponent(
                        garage.longitude
                    );


                mapHtml =
                    `
                    <iframe
                        class="garage-map"
                        src="https://www.google.com/maps?q=${latitude},${longitude}&z=15&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Garage location"
                    ></iframe>
                    `;


                directionsButton =
                    `
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


            modalBody.innerHTML =
                `

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
                                    ${escapeHtml(
                                        garage
                                            .garage_name
                                    )}
                                </h3>

                                <span>
                                    ${escapeHtml(
                                        garage
                                            .district
                                        || ""
                                    )}
                                </span>

                            </div>

                        </div>


                        <div
                            class="garage-info-list"
                        >

                            <div>

                                <span>
                                    📍 Address
                                </span>

                                <strong>
                                    ${escapeHtml(
                                        garage
                                            .address
                                        || "Not provided"
                                    )}
                                </strong>

                            </div>


                            <div>

                                <span>
                                    📞 Mobile
                                </span>

                                <strong>
                                    ${escapeHtml(
                                        garage
                                            .mobile_number
                                        || "Not provided"
                                    )}
                                </strong>

                            </div>


                            <div>

                                <span>
                                    ✉ Email
                                </span>

                                <strong>
                                    ${escapeHtml(
                                        garage
                                            .email
                                        || "Not provided"
                                    )}
                                </strong>

                            </div>


                            <div>

                                <span>
                                    🕒 Opening Hours
                                </span>

                                <strong>
                                    ${hours}
                                </strong>

                            </div>

                        </div>


                        ${
                            garage.description
                            ?
                            `
                            <div
                                class="garage-description-box"
                            >

                                <h4>
                                    About Garage
                                </h4>

                                <p>
                                    ${escapeHtml(
                                        garage
                                            .description
                                    )}
                                </p>

                            </div>
                            `
                            :
                            ""
                        }


                    </div>


                    <div>

                        <div
                            class="garage-popup-map"
                        >

                            ${mapHtml}

                        </div>

                    </div>


                </div>


                <div class="popup-services">

                    <div class="popup-section-title">

                        <h3>
                            Services Offered
                        </h3>

                        <span>
                            ${services.length}
                            services
                        </span>

                    </div>

                    ${servicesHtml}

                </div>


                <div class="garage-popup-actions">

                    ${directionsButton}

                    <a
                        href="login.php?garage_id=${garage.garage_id}"
                        class="btn btn-primary"
                    >
                        📅 Book Service
                    </a>

                </div>

                `;
        }


        // ============================================
        // CLOSE MODAL
        // ============================================

        function closeModal() {

            modal.classList.remove(
                "show"
            );

            modal.setAttribute(
                "aria-hidden",
                "true"
            );

            document.body
                .classList.remove(
                    "modal-open"
                );
        }


        closeButton.addEventListener(
            "click",
            closeModal
        );


        overlay.addEventListener(
            "click",
            closeModal
        );


        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                ) {

                    closeModal();
                }

            }
        );


        // ============================================
        // HELPERS
        // ============================================

        function escapeHtml(value) {

            if (!value) {
                return "";
            }

            const div =
                document.createElement(
                    "div"
                );

            div.textContent = value;

            return div.innerHTML;
        }


        function formatTime(time) {

            const parts =
                time.split(":");

            let hour =
                parseInt(
                    parts[0],
                    10
                );

            const minute =
                parts[1];

            const period =
                hour >= 12
                ? "PM"
                : "AM";

            hour =
                hour % 12 || 12;

            return (
                hour
                + ":"
                + minute
                + " "
                + period
            );
        }

    }
);

</script>
</body>
</html>