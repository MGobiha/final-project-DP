<?php

session_start();

require_once 'config/database.php';
require_once 'includes/maintenance-functions.php';

$message = "";

$old = [
    "account_type" => "",
    "first_name" => "",
    "last_name" => "",
    "email" => "",
    "mobile_number" => "",
    "garage_name" => "",
    "garage_phone" => "",
    "garage_address" => "",
    "garage_district" => "",
    "garage_description" => "",
    "registration_number" => "",
    "vehicle_make" => "",
    "vehicle_model" => "",
    "vehicle_year" => "",
    "current_mileage" => "",
    "average_km_per_month" => "",
    "fuel_type" => "",
    "last_service_type" => "",
    "last_service_date" => "",
    "last_service_mileage" => ""
];

$selectedGarageIds = [];

// =====================================================
// VEHICLE IMAGE UPLOAD HELPER
// =====================================================

function uploadVehicleImages(
    array $files,
    int $vehicleId
): array {

    $result = [
        "paths" => [],
        "absolute_files" => []
    ];

    if (
        empty($files)
        ||
        !isset($files["name"])
        ||
        !is_array($files["name"])
    ) {
        return $result;
    }

    $validIndexes = [];

    foreach ($files["name"] as $index => $name) {
        $error = $files["error"][$index] ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_NO_FILE) {
            $validIndexes[] = $index;
        }
    }

    if (count($validIndexes) > 3) {
        throw new Exception(
            "You can upload a maximum of 3 vehicle images."
        );
    }

    if (count($validIndexes) === 0) {
        return $result;
    }

    $uploadDirectory =
        __DIR__
        . DIRECTORY_SEPARATOR
        . "uploads"
        . DIRECTORY_SEPARATOR
        . "vehicles";

    if (
        !is_dir($uploadDirectory)
        &&
        !mkdir($uploadDirectory, 0775, true)
        &&
        !is_dir($uploadDirectory)
    ) {
        throw new Exception(
            "Unable to create the vehicle image upload folder."
        );
    }

    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $maxFileSize = 5 * 1024 * 1024; // 5 MB per image

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if (!$finfo) {
        throw new Exception(
            "Unable to validate uploaded vehicle images."
        );
    }

    try {

        foreach ($validIndexes as $position => $index) {

            $error =
                $files["error"][$index]
                ?? UPLOAD_ERR_NO_FILE;

            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception(
                    "One of the vehicle images could not be uploaded."
                );
            }

            $tmpName =
                $files["tmp_name"][$index]
                ?? "";

            $fileSize =
                (int) (
                    $files["size"][$index]
                    ?? 0
                );

            if (
                $tmpName === ""
                ||
                !is_uploaded_file($tmpName)
            ) {
                throw new Exception(
                    "Invalid vehicle image upload."
                );
            }

            if (
                $fileSize <= 0
                ||
                $fileSize > $maxFileSize
            ) {
                throw new Exception(
                    "Each vehicle image must be 5 MB or smaller."
                );
            }

            $mimeType =
                finfo_file(
                    $finfo,
                    $tmpName
                );

            if (
                !isset(
                    $allowedMimeTypes[$mimeType]
                )
            ) {
                throw new Exception(
                    "Vehicle images must be JPG, PNG, or WEBP."
                );
            }

            $extension =
                $allowedMimeTypes[$mimeType];

            $randomPart =
                bin2hex(
                    random_bytes(6)
                );

            $fileName =
                "vehicle_"
                . $vehicleId
                . "_"
                . ($position + 1)
                . "_"
                . $randomPart
                . "."
                . $extension;

            $absolutePath =
                $uploadDirectory
                . DIRECTORY_SEPARATOR
                . $fileName;

            if (
                !move_uploaded_file(
                    $tmpName,
                    $absolutePath
                )
            ) {
                throw new Exception(
                    "Unable to save a vehicle image."
                );
            }

            $relativePath =
                "uploads/vehicles/"
                . $fileName;

            $result["paths"][] =
                $relativePath;

            $result["absolute_files"][] =
                $absolutePath;
        }

    } finally {

        finfo_close($finfo);
    }

    return $result;
}


// =====================================================
// APPROVED GARAGES FOR VEHICLE OWNER REGISTRATION
// =====================================================

$garageListSql = "
    SELECT
        garage_id,
        garage_name,
        district,
        address
    FROM garages
    WHERE approval_status = 'approved'
    AND active_status = 1
    ORDER BY garage_name
";

$garageListResult =
    mysqli_query(
        $conn,
        $garageListSql
    );

if (!$garageListResult) {
    die(
        "Garage list query error: "
        . mysqli_error($conn)
    );
}


// =====================================================
// FORM SUBMISSION
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    // -------------------------------------------------
    // ACCOUNT DETAILS
    // -------------------------------------------------

    $accountType =
        trim(
            $_POST["account_type"]
            ?? ""
        );

    $firstName =
        trim(
            $_POST["first_name"]
            ?? ""
        );

    $lastName =
        trim(
            $_POST["last_name"]
            ?? ""
        );

    $email =
        trim(
            $_POST["email"]
            ?? ""
        );

    $mobileNumber =
        trim(
            $_POST["mobile_number"]
            ?? ""
        );

    $password =
        $_POST["password"]
        ?? "";

    $confirmPassword =
        $_POST["confirm_password"]
        ?? "";

    $maintenanceSms =
        isset(
            $_POST["maintenance_sms"]
        )
        ? 1
        : 0;

    $appointmentSms =
        isset(
            $_POST["appointment_sms"]
        )
        ? 1
        : 0;

    $newsSms =
        isset(
            $_POST["news_sms"]
        )
        ? 1
        : 0;


    // -------------------------------------------------
    // GARAGE ADMIN DETAILS
    // -------------------------------------------------

    $garageName =
        trim(
            $_POST["garage_name"]
            ?? ""
        );

    $garagePhone =
        trim(
            $_POST["garage_phone"]
            ?? ""
        );

    $garageAddress =
        trim(
            $_POST["garage_address"]
            ?? ""
        );

    $garageDistrict =
        trim(
            $_POST["garage_district"]
            ?? ""
        );

    $garageDescription =
        trim(
            $_POST["garage_description"]
            ?? ""
        );


    // -------------------------------------------------
    // SELECTED GARAGES
    // -------------------------------------------------

    $selectedGarageIds =
        $_POST["garage_ids"]
        ?? [];

    if (
        !is_array(
            $selectedGarageIds
        )
    ) {
        $selectedGarageIds = [];
    }

    $selectedGarageIds =
        array_values(
            array_unique(
                array_filter(
                    array_map(
                        "intval",
                        $selectedGarageIds
                    ),
                    fn($id) => $id > 0
                )
            )
        );


    // -------------------------------------------------
    // VEHICLE DETAILS
    // -------------------------------------------------

    $registrationNumber =
        trim(
            $_POST["registration_number"]
            ?? ""
        );

    $vehicleMake =
        trim(
            $_POST["vehicle_make"]
            ?? ""
        );

    $vehicleModel =
        trim(
            $_POST["vehicle_model"]
            ?? ""
        );

    $vehicleYear =
        trim(
            $_POST["vehicle_year"]
            ?? ""
        );

    $currentMileage =
        trim(
            $_POST["current_mileage"]
            ?? ""
        );

    $averageKmPerMonth =
        trim(
            $_POST["average_km_per_month"]
            ?? ""
        );

    $fuelType =
        trim(
            $_POST["fuel_type"]
            ?? ""
        );

    $lastServiceType =
        trim(
            $_POST["last_service_type"]
            ?? ""
        );

    $lastServiceDate =
        trim(
            $_POST["last_service_date"]
            ?? ""
        );

    $lastServiceMileage =
        trim(
            $_POST["last_service_mileage"]
            ?? ""
        );


    // -------------------------------------------------
    // KEEP ENTERED VALUES IF VALIDATION FAILS
    // -------------------------------------------------

    $old = [
        "account_type" => $accountType,
        "first_name" => $firstName,
        "last_name" => $lastName,
        "email" => $email,
        "mobile_number" => $mobileNumber,
        "garage_name" => $garageName,
        "garage_phone" => $garagePhone,
        "garage_address" => $garageAddress,
        "garage_district" => $garageDistrict,
        "garage_description" => $garageDescription,
        "registration_number" => $registrationNumber,
        "vehicle_make" => $vehicleMake,
        "vehicle_model" => $vehicleModel,
        "vehicle_year" => $vehicleYear,
        "current_mileage" => $currentMileage,
        "average_km_per_month" => $averageKmPerMonth,
        "fuel_type" => $fuelType,
        "last_service_type" => $lastServiceType,
        "last_service_date" => $lastServiceDate,
        "last_service_mileage" => $lastServiceMileage
    ];


    // -------------------------------------------------
    // ALLOWED VALUES
    // -------------------------------------------------

    $allowedTypes = [
        "vehicle_owner",
        "garage_admin"
    ];

    $allowedDistricts = [
        "Jaffna",
        "Kilinochchi",
        "Mullaitivu",
        "Mannar",
        "Vavuniya"
    ];

    $allowedFuelTypes = [
        "Petrol",
        "Diesel",
        "Hybrid",
        "Electric"
    ];

    $allowedServiceTypes = [
        "",
        "Engine Oil Change",
        "General Service",
        "Brake Service",
        "Battery Service",
        "Full Service",
        "Other"
    ];


    // -------------------------------------------------
    // SERVER-SIDE VALIDATION
    // -------------------------------------------------

    if (
        !in_array(
            $accountType,
            $allowedTypes,
            true
        )
    ) {

        $message =
            "Please select a valid account type.";

    } elseif (
        $firstName === ""
        ||
        $lastName === ""
        ||
        $email === ""
        ||
        $mobileNumber === ""
        ||
        $password === ""
        ||
        $confirmPassword === ""
    ) {

        $message =
            "Please fill in all required account fields.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

    } elseif (
        $password !== $confirmPassword
    ) {

        $message =
            "Passwords do not match.";

    } elseif (
        strlen($password) < 6
    ) {

        $message =
            "Password must contain at least 6 characters.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        count(
            $selectedGarageIds
        ) === 0
    ) {

        $message =
            "Please select at least one garage.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        (
            $registrationNumber === ""
            ||
            $vehicleMake === ""
            ||
            $vehicleModel === ""
        )
    ) {

        $message =
            "Please enter the required vehicle details.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $vehicleYear !== ""
        &&
        (
            !ctype_digit($vehicleYear)
            ||
            (int) $vehicleYear < 1950
            ||
            (int) $vehicleYear > 2100
        )
    ) {

        $message =
            "Please enter a valid vehicle year.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $currentMileage !== ""
        &&
        (
            !ctype_digit($currentMileage)
            ||
            (int) $currentMileage < 0
        )
    ) {

        $message =
            "Please enter a valid current mileage.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $averageKmPerMonth !== ""
        &&
        (
            !ctype_digit($averageKmPerMonth)
            ||
            (int) $averageKmPerMonth < 0
        )
    ) {

        $message =
            "Please enter a valid average KM per month.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $fuelType !== ""
        &&
        !in_array(
            $fuelType,
            $allowedFuelTypes,
            true
        )
    ) {

        $message =
            "Please select a valid fuel type.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        !in_array(
            $lastServiceType,
            $allowedServiceTypes,
            true
        )
    ) {

        $message =
            "Please select a valid last service type.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $lastServiceMileage !== ""
        &&
        (
            !ctype_digit($lastServiceMileage)
            ||
            (int) $lastServiceMileage < 0
        )
    ) {

        $message =
            "Please enter a valid last service mileage.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $currentMileage !== ""
        &&
        $lastServiceMileage !== ""
        &&
        (int) $lastServiceMileage
            >
        (int) $currentMileage
    ) {

        $message =
            "Last service mileage cannot be greater than the current mileage.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $lastServiceDate !== ""
        &&
        strtotime($lastServiceDate) === false
    ) {

        $message =
            "Please enter a valid last service date.";

    } elseif (
        $accountType === "vehicle_owner"
        &&
        $lastServiceDate !== ""
        &&
        strtotime($lastServiceDate)
            >
        strtotime(date("Y-m-d"))
    ) {

        $message =
            "Last service date cannot be in the future.";

    } elseif (
        $accountType === "garage_admin"
        &&
        (
            $garageName === ""
            ||
            $garagePhone === ""
            ||
            $garageAddress === ""
            ||
            $garageDistrict === ""
        )
    ) {

        $message =
            "Please complete all required garage information.";

    } elseif (
        $accountType === "garage_admin"
        &&
        !in_array(
            $garageDistrict,
            $allowedDistricts,
            true
        )
    ) {

        $message =
            "Please select a valid Northern Province district.";

    } else {


        // =================================================
        // CHECK DUPLICATE EMAIL
        // =================================================

        $checkSql = "
            SELECT user_id
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $checkStmt =
            mysqli_prepare(
                $conn,
                $checkSql
            );

        if (!$checkStmt) {

            $message =
                "Unable to validate the email address.";

        } else {

            mysqli_stmt_bind_param(
                $checkStmt,
                "s",
                $email
            );

            mysqli_stmt_execute(
                $checkStmt
            );

            $checkResult =
                mysqli_stmt_get_result(
                    $checkStmt
                );

            if (
                mysqli_num_rows(
                    $checkResult
                ) > 0
            ) {

                $message =
                    "An account already exists with this email address.";

            } else {

                $hashedPassword =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                // =================================================
                // VEHICLE OWNER REGISTRATION
                // =================================================

                if (
                    $accountType
                    === "vehicle_owner"
                ) {

                    $uploadedVehicleFiles = [];

                    mysqli_begin_transaction(
                        $conn
                    );

                    try {

                        $role =
                            "vehicle_owner";


                        // -----------------------------------------
                        // CREATE USER
                        // -----------------------------------------

                        $userSql = "
                            INSERT INTO users
                            (
                                first_name,
                                last_name,
                                email,
                                mobile_number,
                                password,
                                maintenance_sms,
                                appointment_sms,
                                news_sms,
                                role
                            )
                            VALUES
                            (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?
                            )
                        ";

                        $userStmt =
                            mysqli_prepare(
                                $conn,
                                $userSql
                            );

                        if (!$userStmt) {
                            throw new Exception(
                                "Unable to prepare vehicle owner account."
                            );
                        }

                        mysqli_stmt_bind_param(
                            $userStmt,
                            "sssssiiis",
                            $firstName,
                            $lastName,
                            $email,
                            $mobileNumber,
                            $hashedPassword,
                            $maintenanceSms,
                            $appointmentSms,
                            $newsSms,
                            $role
                        );

                        if (
                            !mysqli_stmt_execute(
                                $userStmt
                            )
                        ) {
                            throw new Exception(
                                "Unable to create vehicle owner account: "
                                . mysqli_stmt_error(
                                    $userStmt
                                )
                            );
                        }

                        $vehicleOwnerId =
                            mysqli_insert_id(
                                $conn
                            );


                        // -----------------------------------------
                        // CONVERT OPTIONAL VEHICLE VALUES
                        // -----------------------------------------

                        $yearValue =
                            $vehicleYear === ""
                            ? null
                            : (int) $vehicleYear;

                        $mileageValue =
                            $currentMileage === ""
                            ? 0
                            : (int) $currentMileage;

                        $averageKmValue =
                            $averageKmPerMonth === ""
                            ? null
                            : (int) $averageKmPerMonth;

                        $lastServiceDateValue =
                            $lastServiceDate === ""
                            ? null
                            : $lastServiceDate;

                        $lastServiceMileageValue =
                            $lastServiceMileage === ""
                            ? null
                            : (int) $lastServiceMileage;


                        // -----------------------------------------
                        // CREATE VEHICLE
                        // -----------------------------------------

                        $vehicleSql = "
                            INSERT INTO vehicles
                            (
                                user_id,
                                registration_number,
                                make,
                                model,
                                manufacture_year,
                                current_mileage,
                                average_km_per_month,
                                fuel_type,
                                last_service_type,
                                last_service_date,
                                last_service_mileage
                            )
                            VALUES
                            (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                            )
                        ";

                        $vehicleStmt =
                            mysqli_prepare(
                                $conn,
                                $vehicleSql
                            );

                        if (!$vehicleStmt) {
                            throw new Exception(
                                "Unable to prepare vehicle registration: "
                                . mysqli_error(
                                    $conn
                                )
                            );
                        }

                        mysqli_stmt_bind_param(
                            $vehicleStmt,
                            "isssiiisssi",
                            $vehicleOwnerId,
                            $registrationNumber,
                            $vehicleMake,
                            $vehicleModel,
                            $yearValue,
                            $mileageValue,
                            $averageKmValue,
                            $fuelType,
                            $lastServiceType,
                            $lastServiceDateValue,
                            $lastServiceMileageValue
                        );

                        if (
                            !mysqli_stmt_execute(
                                $vehicleStmt
                            )
                        ) {
                            throw new Exception(
                                "Unable to save vehicle details: "
                                . mysqli_stmt_error(
                                    $vehicleStmt
                                )
                            );
                        }

                        $vehicleId =
                            mysqli_insert_id(
                                $conn
                            );


                        // -----------------------------------------
                        // UPLOAD MAXIMUM 3 VEHICLE IMAGES
                        // -----------------------------------------

                        $imageUpload =
                            uploadVehicleImages(
                                $_FILES["vehicle_images"]
                                ?? [],
                                $vehicleId
                            );

                        $imagePaths =
                            $imageUpload[
                                "paths"
                            ];

                        $uploadedVehicleFiles =
                            $imageUpload[
                                "absolute_files"
                            ];

                        $image1 =
                            $imagePaths[0]
                            ?? null;

                        $image2 =
                            $imagePaths[1]
                            ?? null;

                        $image3 =
                            $imagePaths[2]
                            ?? null;

                        // Keep the first image in the legacy
                        // vehicle_image column as well.
                        $mainVehicleImage =
                            $image1;

                        $imageSql = "
                            UPDATE vehicles
                            SET
                                vehicle_image = ?,
                                vehicle_image_1 = ?,
                                vehicle_image_2 = ?,
                                vehicle_image_3 = ?
                            WHERE vehicle_id = ?
                        ";

                        $imageStmt =
                            mysqli_prepare(
                                $conn,
                                $imageSql
                            );

                        if (!$imageStmt) {
                            throw new Exception(
                                "Unable to prepare vehicle image update: "
                                . mysqli_error(
                                    $conn
                                )
                            );
                        }

                        mysqli_stmt_bind_param(
                            $imageStmt,
                            "ssssi",
                            $mainVehicleImage,
                            $image1,
                            $image2,
                            $image3,
                            $vehicleId
                        );

                        if (
                            !mysqli_stmt_execute(
                                $imageStmt
                            )
                        ) {
                            throw new Exception(
                                "Unable to save vehicle image paths: "
                                . mysqli_stmt_error(
                                    $imageStmt
                                )
                            );
                        }


                        // -----------------------------------------
                        // CREATE AUTOMATIC MAINTENANCE SCHEDULES
                        // -----------------------------------------

                        generateVehicleReminders(
                            $conn,
                            $vehicleId,
                            $vehicleOwnerId
                        );


                        // -----------------------------------------
                        // CREATE GARAGE APPROVAL REQUESTS
                        // -----------------------------------------

                        $verifyGarageSql = "
                            SELECT garage_id
                            FROM garages
                            WHERE garage_id = ?
                            AND approval_status = 'approved'
                            AND active_status = 1
                            LIMIT 1
                        ";

                        $requestSql = "
                            INSERT INTO garage_customer_requests
                            (
                                garage_id,
                                vehicle_owner_id,
                                request_status,
                                requested_by
                            )
                            VALUES
                            (
                                ?, ?, 'pending', 'vehicle_owner'
                            )
                        ";

                        foreach (
                            $selectedGarageIds
                            as $selectedGarageId
                        ) {

                            $verifyGarageStmt =
                                mysqli_prepare(
                                    $conn,
                                    $verifyGarageSql
                                );

                            if (
                                !$verifyGarageStmt
                            ) {
                                throw new Exception(
                                    "Unable to verify selected garage."
                                );
                            }

                            mysqli_stmt_bind_param(
                                $verifyGarageStmt,
                                "i",
                                $selectedGarageId
                            );

                            mysqli_stmt_execute(
                                $verifyGarageStmt
                            );

                            $verifyGarageResult =
                                mysqli_stmt_get_result(
                                    $verifyGarageStmt
                                );

                            if (
                                mysqli_num_rows(
                                    $verifyGarageResult
                                ) !== 1
                            ) {
                                throw new Exception(
                                    "One of the selected garages is not available."
                                );
                            }

                            $requestStmt =
                                mysqli_prepare(
                                    $conn,
                                    $requestSql
                                );

                            if (!$requestStmt) {
                                throw new Exception(
                                    "Unable to prepare garage request."
                                );
                            }

                            mysqli_stmt_bind_param(
                                $requestStmt,
                                "ii",
                                $selectedGarageId,
                                $vehicleOwnerId
                            );

                            if (
                                !mysqli_stmt_execute(
                                    $requestStmt
                                )
                            ) {
                                throw new Exception(
                                    "Unable to send garage request: "
                                    . mysqli_stmt_error(
                                        $requestStmt
                                    )
                                );
                            }
                        }


                        mysqli_commit(
                            $conn
                        );

                        $_SESSION[
                            "success_message"
                        ] =
                            "Registration successful. Your garage request(s) were sent for approval. Please sign in.";

                        header(
                            "Location: login.php"
                        );

                        exit();

                    } catch (Throwable $e) {

                        mysqli_rollback(
                            $conn
                        );

                        foreach (
                            $uploadedVehicleFiles
                            as $uploadedFile
                        ) {

                            if (
                                is_file(
                                    $uploadedFile
                                )
                            ) {
                                @unlink(
                                    $uploadedFile
                                );
                            }
                        }

                        $message =
                            "Vehicle owner registration failed. "
                            . $e->getMessage();
                    }
                }


                // =================================================
                // GARAGE ADMIN REGISTRATION
                // =================================================

                if (
                    $accountType
                    === "garage_admin"
                ) {

                    mysqli_begin_transaction(
                        $conn
                    );

                    try {

                        $role =
                            "garage_admin";


                        // -----------------------------------------
                        // CREATE GARAGE ADMIN USER
                        // -----------------------------------------

                        $userSql = "
                            INSERT INTO users
                            (
                                first_name,
                                last_name,
                                email,
                                mobile_number,
                                password,
                                maintenance_sms,
                                appointment_sms,
                                news_sms,
                                role
                            )
                            VALUES
                            (
                                ?, ?, ?, ?, ?, 0, 0, 0, ?
                            )
                        ";

                        $userStmt =
                            mysqli_prepare(
                                $conn,
                                $userSql
                            );

                        if (!$userStmt) {
                            throw new Exception(
                                "Unable to prepare garage administrator account."
                            );
                        }

                        mysqli_stmt_bind_param(
                            $userStmt,
                            "ssssss",
                            $firstName,
                            $lastName,
                            $email,
                            $mobileNumber,
                            $hashedPassword,
                            $role
                        );

                        if (
                            !mysqli_stmt_execute(
                                $userStmt
                            )
                        ) {
                            throw new Exception(
                                "Unable to create garage administrator account: "
                                . mysqli_stmt_error(
                                    $userStmt
                                )
                            );
                        }

                        $garageAdminUserId =
                            mysqli_insert_id(
                                $conn
                            );

                        $ownerName =
                            trim(
                                $firstName
                                . " "
                                . $lastName
                            );


                        // -----------------------------------------
                        // CREATE GARAGE
                        // -----------------------------------------

                        $garageSql = "
                            INSERT INTO garages
                            (
                                owner_user_id,
                                garage_name,
                                owner_name,
                                email,
                                mobile_number,
                                address,
                                district,
                                description,
                                approval_status,
                                active_status
                            )
                            VALUES
                            (
                                ?, ?, ?, ?, ?, ?, ?, ?,
                                'pending',
                                0
                            )
                        ";

                        $garageStmt =
                            mysqli_prepare(
                                $conn,
                                $garageSql
                            );

                        if (!$garageStmt) {
                            throw new Exception(
                                "Unable to prepare garage registration."
                            );
                        }

                        mysqli_stmt_bind_param(
                            $garageStmt,
                            "isssssss",
                            $garageAdminUserId,
                            $garageName,
                            $ownerName,
                            $email,
                            $garagePhone,
                            $garageAddress,
                            $garageDistrict,
                            $garageDescription
                        );

                        if (
                            !mysqli_stmt_execute(
                                $garageStmt
                            )
                        ) {
                            throw new Exception(
                                "Unable to create garage record: "
                                . mysqli_stmt_error(
                                    $garageStmt
                                )
                            );
                        }

                        mysqli_commit(
                            $conn
                        );

                        $_SESSION[
                            "success_message"
                        ] =
                            "Garage registration submitted successfully. Your garage is waiting for System Admin approval.";

                        header(
                            "Location: login.php"
                        );

                        exit();

                    } catch (Throwable $e) {

                        mysqli_rollback(
                            $conn
                        );

                        $message =
                            "Garage registration failed. "
                            . $e->getMessage();
                    }
                }
            }
        }
    }
}


// Re-run because the first result may have been consumed.
$garageListResult =
    mysqli_query(
        $conn,
        $garageListSql
    );

if (!$garageListResult) {
    die(
        "Garage list query error: "
        . mysqli_error($conn)
    );
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - AutoTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Inter", sans-serif; background: #f5f8fc; color: #172033; }

        .register-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 38% 62%;
            background: #ffffff;
        }

        .register-visual {
         position: sticky;
    top: 0;

    height: 100vh;

    padding: 45px 48px;

    display: flex;
    align-items: center;

    overflow: hidden;

    background:
        radial-gradient(
            circle at top right,
            rgba(255, 255, 255, .16),
            transparent 35%
        ),
        linear-gradient(
            145deg,
            #0f62fe,
            #0b3d91
        );

    color: #ffffff;

        }

        .register-visual-inner {
            width: 100%;
            max-width: 470px;
            margin: 0 auto; 
        }
        .register-brand { 
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 35px;
            font-size: 21px;
            font-weight: 800; 
        }
        .register-brand-badge {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                background: #ffffff;
                color: #0f62fe;
                font-weight: 800;
        }
        /* Main heading */

        .register-visual h1 {
                margin: 0 0 18px;
                font-size: clamp(34px, 3.2vw, 50px);
                line-height: 1.08;
                letter-spacing: -1.2px;
        }
        .register-visual p {
            margin: 0;

            color: rgba(
                255,
                255,
                255,
                .86
            );

            font-size: 15px;
            line-height: 1.7;
        }
        .register-features {
                margin-top: 28px;

                display: grid;

                gap: 12px;
            }
        .register-feature {
                display: flex;

                gap: 10px;

                align-items: flex-start;

                color: rgba(
                    255,
                    255,
                    255,
                    .92
                );

                font-size: 14px;

                line-height: 1.5;
            }
       .register-panel {
            min-width: 0;

            padding: 35px 45px 50px;

            display: flex;

            justify-content: center;
            align-items: flex-start;

            background: #ffffff;
        }

        .register-card {
            width: 100%;

            /* Form can now use more space */
            max-width: 760px;
        }
        .register-card-head { margin-bottom: 22px; }
        .register-card-head h2 { margin: 0 0 8px; font-size: 29px; }
        .register-card-head p { margin: 0; color: #667085; }

        .form-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 17px 18px; }
        .field-full { grid-column: 1 / -1; }
        .field label { display: block; margin-bottom: 7px; color: #344054; font-size: 14px; font-weight: 600; }
        .field input, .field select, .field textarea { width: 100%; border: 1px solid #d0d5dd; border-radius: 10px; padding: 10px 11px; font: inherit; background: #fff; color: #101828; outline: none; transition: border-color .2s ease, box-shadow .2s ease; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: #0f62fe; box-shadow: 0 0 0 3px rgba(15,98,254,.12); }
        .field textarea { min-height: 86px; resize: vertical; }
        .field small { display: block; margin-top: 6px; color: #667085; line-height: 1.5; }

        .garage-fields { grid-column: 1 / -1; display: none; padding: 17px; border: 1px solid #dce6f3; border-radius: 14px; background: #f8fbff; }
        .garage-fields h3 { margin: 0 0 15px; font-size: 18px; }
        .garage-fields-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 17px 18px; }

        .sms-box { grid-column: 1 / -1; display: grid; gap: 10px; padding: 15px; border-radius: 12px; background: #f8fafc; border: 1px solid #e4e7ec; }
        .sms-box label { display: flex; align-items: flex-start; gap: 9px; margin: 0; font-weight: 500; }
        .sms-box input[type="checkbox"] { width: auto; margin-top: 3px; }

        .alert { grid-column: 1 / -1; padding: 12px 14px; border-radius: 10px; }
        .alert-danger { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
        .register-actions { grid-column: 1 / -1; margin-top: 2px; }
        .register-actions .btn { width: 100%; }
        .signin-link { margin: 14px 0 0; text-align: center; color: #667085; }
        .signin-link a { color: #0f62fe; font-weight: 700; text-decoration: none; }

        @media (max-width: 980px) {
            .register-page { grid-template-columns: 1fr; }
            .register-visual { padding: 42px 26px; }
            .register-panel { padding: 30px 22px; }
        }

        @media (max-width: 620px) {
            .form-grid-2, .garage-fields-grid, .vehicle-fields-grid, .garage-select-grid { grid-template-columns: 1fr; }
            .register-panel { padding: 24px 16px; }
        }

        .conditional-section { grid-column: 1 / -1; padding: 17px; border: 1px solid #dce6f3; border-radius: 14px; background: #f8fbff; }
        .conditional-section h3 { margin: 0 0 5px; font-size: 18px; }
        .section-description { margin: 0 0 15px; color: #667085; font-size: 13px; line-height: 1.5; }
        .vehicle-fields-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 17px 18px; }
        .garage-select-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .garage-select-option { display: flex; align-items: flex-start; gap: 10px; padding: 13px; border: 1px solid #d8e0ea; border-radius: 11px; background: #fff; cursor: pointer; }
        .garage-select-option:hover { border-color: #0f62fe; background: #f8fbff; }
        .garage-select-option input { width: auto; margin-top: 3px; }
        .garage-select-option strong { display: block; font-size: 14px; color: #172033; }
        .garage-select-option small { display: block; margin-top: 4px; color: #667085; font-size: 12px; line-height: 1.4; }

        /* =========================================
   TABLET
   ========================================= */

@media (max-width: 1050px) {

    .register-page {
        grid-template-columns: 42% 58%;
    }

    .register-visual {
        padding: 40px 30px;
    }

    .register-panel {
        padding: 35px 30px;
    }

}


/* =========================================
   MOBILE
   ========================================= */

@media (max-width: 800px) {

    .register-page {
        display: block;
    }

    .register-visual {
        position: relative;

        height: auto;
        min-height: auto;

        padding: 40px 25px;
    }

    .register-visual-inner {
        max-width: 650px;
    }

    .register-visual h1 {
        font-size: 36px;
    }

    .register-panel {
        padding: 30px 20px;
    }

    .register-card {
        max-width: 650px;
    }

}

    </style>
</head>
<body>

<div class="register-page">
    <section class="register-visual">
        <div class="register-visual-inner">
            <div class="register-brand">
                <span class="register-brand-badge">A</span>
                <span>AutoTrack</span>
            </div>
            <h1>Create your digital vehicle or garage account.</h1>
            <p>Manage vehicle records, garage relationships, service history, maintenance reminders, appointments and service information from one place.</p>
            <div class="register-features">
                <div class="register-feature"><span>✓</span><span>Vehicle owners can connect with multiple approved garages.</span></div>
                <div class="register-feature"><span>✓</span><span>Garage registrations are reviewed by the System Admin.</span></div>
                <div class="register-feature"><span>✓</span><span>Maintenance and appointment alerts can be sent to customers.</span></div>
            </div>
        </div>
    </section>

    <section class="register-panel">
        <div class="register-card">
            <div class="register-card-head">
                <h2>Create Account</h2>
                <p>Select the account type and complete the information below.</p>
            </div>

           <form method="POST" enctype="multipart/form-data">
                <div class="form-grid-2">
                    <div class="field field-full">
                        <label for="accountType">Account Type</label>
                        <select name="account_type" id="accountType" required>
                            <option value="">Select account type</option>
                            <option value="vehicle_owner" <?= $old["account_type"] === "vehicle_owner" ? "selected" : "" ?>>Vehicle Owner</option>
                            <option value="garage_admin" <?= $old["account_type"] === "garage_admin" ? "selected" : "" ?>>Garage / Garage Admin</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="firstName">First Name</label>
                        <input type="text" name="first_name" id="firstName" value="<?= htmlspecialchars($old["first_name"]) ?>" placeholder="Enter first name" required>
                    </div>

                    <div class="field">
                        <label for="lastName">Last Name</label>
                        <input type="text" name="last_name" id="lastName" value="<?= htmlspecialchars($old["last_name"]) ?>" placeholder="Enter last name" required>
                    </div>

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($old["email"]) ?>" placeholder="example@email.com" required>
                    </div>

                    <div class="field">
                        <label for="mobileNumber">Mobile Number</label>
                        <input type="tel" name="mobile_number" id="mobileNumber" value="<?= htmlspecialchars($old["mobile_number"]) ?>" placeholder="+94771234567" required>
                        <small>Used for account and customer communication.</small>
                    </div>


                    <div id="vehicleOwnerGarageFields" class="conditional-section">
                        <h3>Select Garage(s)</h3>
                        <p class="section-description">
                            Choose one or more approved garages. Your request will be sent to each garage administrator for approval.
                        </p>

                        <div class="garage-select-grid">
                            <?php if (mysqli_num_rows($garageListResult) > 0): ?>
                                <?php while ($garageItem = mysqli_fetch_assoc($garageListResult)): ?>
                                    <?php
                                    $garageItemId = (int)$garageItem["garage_id"];
                                    $isSelected = in_array($garageItemId, $selectedGarageIds, true);
                                    ?>
                                    <label class="garage-select-option">
                                        <input
                                            type="checkbox"
                                            name="garage_ids[]"
                                            value="<?= $garageItemId ?>"
                                            <?= $isSelected ? "checked" : "" ?>
                                        >
                                        <span>
                                            <strong><?= htmlspecialchars($garageItem["garage_name"]) ?></strong>
                                            <small>
                                                <?= htmlspecialchars($garageItem["district"] ?? "") ?>
                                                <?php if (!empty($garageItem["address"])): ?>
                                                    <br><?= htmlspecialchars($garageItem["address"]) ?>
                                                <?php endif; ?>
                                            </small>
                                        </span>
                                    </label>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="muted">No approved garages are currently available.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="vehicleDetailsSection" class="conditional-section">
                        <h3>Vehicle Details</h3>
                        <p class="section-description">
                            Register your first vehicle. You can add more vehicles later from your dashboard.
                        </p>

                        <div class="vehicle-fields-grid">
                            <div class="field">
                                <label for="registrationNumber">Registration Number *</label>
                                <input
                                    type="text"
                                    name="registration_number"
                                    id="registrationNumber"
                                    value="<?= htmlspecialchars($old["registration_number"]) ?>"
                                    placeholder="Example: WP CAB-1234"
                                >
                            </div>

                            <div class="field">
                                <label for="vehicleMake">Make *</label>

                                <select
                                    name="vehicle_make"
                                    id="vehicleMake"
                                >
                                    <option value="">Select make</option>
                                    <option value="Toyota">Toyota</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Nissan">Nissan</option>
                                    <option value="Suzuki">Suzuki</option>
                                    <option value="Mitsubishi">Mitsubishi</option>
                                    <option value="Mazda">Mazda</option>
                                    <option value="Hyundai">Hyundai</option>
                                    <option value="Kia">Kia</option>
                                    <option value="Daihatsu">Daihatsu</option>
                                    <option value="Perodua">Perodua</option>
                                    <option value="Isuzu">Isuzu</option>
                                    <option value="Ford">Ford</option>
                                    <option value="BMW">BMW</option>
                                    <option value="Mercedes-Benz">Mercedes-Benz</option>
                                    <option value="Audi">Audi</option>
                                    <option value="Tata">Tata</option>
                                    <option value="Mahindra">Mahindra</option>
                                    <option value="MG">MG</option>
                                    <option value="BYD">BYD</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="vehicleModel">Model *</label>

                                <select
                                    name="vehicle_model"
                                    id="vehicleModel"
                                >
                                    <option value="">Select make first</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="vehicleYear">Year</label>

                                <select
                                    name="vehicle_year"
                                    id="vehicleYear"
                                >
                                    <option value="">Select model first</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="currentMileage">Current Mileage (km)</label>
                                <input
                                    type="number"
                                    name="current_mileage"
                                    id="currentMileage"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($old["current_mileage"]) ?>"
                                    placeholder="45000"
                                >
                            </div>

                            <div class="field">
                                <label for="fuelType">Fuel Type</label>

                                <select name="fuel_type" id="fuelType">
                                    <option value="">Select model first</option>
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Electric">Electric</option>
                                </select>

                                <small>
                                    Automatically selected from the model. You can change it if your exact vehicle variant is different.
                                </small>
                            </div>
                            <div class="field">
                                <label for="averageKmPerMonth">
                                    Average KM Per Month
                                </label>

                                <input
                                    type="number"
                                    name="average_km_per_month"
                                    id="averageKmPerMonth"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($old["average_km_per_month"]) ?>"
                                    placeholder="Example: 1200"
                                >

                                <small>
                                    Used to estimate future maintenance dates.
                                </small>
                            </div>


                            <div class="field">
                                <label for="lastServiceType">
                                    Last Service Type
                                </label>

                                <select
                                    name="last_service_type"
                                    id="lastServiceType"
                                >
                                    <option value="">
                                        Select service
                                    </option>

                                    <option value="Engine Oil Change" <?= $old["last_service_type"] === "Engine Oil Change" ? "selected" : "" ?>>
                                        Engine Oil Change
                                    </option>

                                    <option value="General Service" <?= $old["last_service_type"] === "General Service" ? "selected" : "" ?>>
                                        General Service
                                    </option>

                                    <option value="Brake Service" <?= $old["last_service_type"] === "Brake Service" ? "selected" : "" ?>>
                                        Brake Service
                                    </option>

                                    <option value="Battery Service" <?= $old["last_service_type"] === "Battery Service" ? "selected" : "" ?>>
                                        Battery Service
                                    </option>

                                    <option value="Full Service" <?= $old["last_service_type"] === "Full Service" ? "selected" : "" ?>>
                                        Full Service
                                    </option>

                                    <option value="Other" <?= $old["last_service_type"] === "Other" ? "selected" : "" ?>>
                                        Other
                                    </option>
                                </select>
                            </div>


                            <div class="field">
                                <label for="lastServiceDate">
                                    Last Service Date
                                </label>

                                <input
                                    type="date"
                                    name="last_service_date"
                                    id="lastServiceDate"
                                    max="<?= date('Y-m-d') ?>"
                                    value="<?= htmlspecialchars($old["last_service_date"]) ?>"
                                >
                            </div>


                            <div class="field">
                                <label for="lastServiceMileage">
                                    Mileage at Last Service
                                </label>

                                <input
                                    type="number"
                                    name="last_service_mileage"
                                    id="lastServiceMileage"
                                    min="0"
                                    step="1"
                                    value="<?= htmlspecialchars($old["last_service_mileage"]) ?>"
                                    placeholder="Example: 55000"
                                >
                            </div>


                            <div class="field field-full">

                                <label>
                                    Vehicle Images
                                </label>

                                <input
                                    type="file"
                                    name="vehicle_images[]"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    id="vehicleImages"
                                >

                                <small>
                                    Upload up to 3 images.
                                    JPG, PNG or WEBP.
                                </small>

                            </div>

                        </div>
                    </div>

                    <div id="garageFields" class="garage-fields">
                        <h3>Garage Information</h3>
                        <div class="garage-fields-grid">
                            <div class="field">
                                <label for="garageName">Garage Name</label>
                                <input type="text" name="garage_name" id="garageName" value="<?= htmlspecialchars($old["garage_name"]) ?>" placeholder="Enter garage name">
                            </div>

                            <div class="field">
                                <label for="garagePhone">Garage Phone</label>
                                <input type="tel" name="garage_phone" id="garagePhone" value="<?= htmlspecialchars($old["garage_phone"]) ?>" placeholder="+94771234567">
                            </div>

                            <div class="field field-full">
                                <label for="garageAddress">Garage Address</label>
                                <textarea name="garage_address" id="garageAddress" placeholder="Enter complete garage address"><?= htmlspecialchars($old["garage_address"]) ?></textarea>
                            </div>

                            <div class="field">
                                <label for="garageDistrict">District</label>
                                <select name="garage_district" id="garageDistrict">
                                    <option value="">Select district</option>
                                    <?php foreach (["Jaffna", "Kilinochchi", "Mullaitivu", "Mannar", "Vavuniya"] as $district): ?>
                                        <option value="<?= htmlspecialchars($district) ?>" <?= $old["garage_district"] === $district ? "selected" : "" ?>><?= htmlspecialchars($district) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="garageDescription">Garage Description</label>
                                <textarea name="garage_description" id="garageDescription" placeholder="Describe your garage services"><?= htmlspecialchars($old["garage_description"]) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Minimum 6 characters" minlength="6" required>
                    </div>

                    <div class="field">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Repeat password" minlength="6" required>
                    </div>

                    <div id="ownerSmsFields" class="sms-box">
                        <label><input type="checkbox" name="maintenance_sms" <?= $_SERVER["REQUEST_METHOD"] !== "POST" || isset($_POST["maintenance_sms"]) ? "checked" : "" ?>><span>Send maintenance reminders by SMS</span></label>
                        <label><input type="checkbox" name="appointment_sms" <?= $_SERVER["REQUEST_METHOD"] !== "POST" || isset($_POST["appointment_sms"]) ? "checked" : "" ?>><span>Send appointment alerts by SMS</span></label>
                        <label><input type="checkbox" name="news_sms" <?= isset($_POST["news_sms"]) ? "checked" : "" ?>><span>Send automotive news alerts by SMS</span></label>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <div class="register-actions">
                        <button type="submit" class="btn btn-primary">Create Account</button>
                        <p class="signin-link">Already registered? <a href="login.php">Sign in</a></p>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const accountType = document.getElementById("accountType");

    const garageFields = document.getElementById("garageFields");
    const ownerSmsFields = document.getElementById("ownerSmsFields");
    const vehicleOwnerGarageFields = document.getElementById("vehicleOwnerGarageFields");
    const vehicleDetailsSection = document.getElementById("vehicleDetailsSection");

    const garageName = document.getElementById("garageName");
    const garagePhone = document.getElementById("garagePhone");
    const garageAddress = document.getElementById("garageAddress");
    const garageDistrict = document.getElementById("garageDistrict");

const registrationNumber =
    document.getElementById(
        "registrationNumber"
    );

const vehicleMake =
    document.getElementById(
        "vehicleMake"
    );

const vehicleModel =
    document.getElementById(
        "vehicleModel"
    );

const vehicleYear =
    document.getElementById(
        "vehicleYear"
    );

const fuelType =
    document.getElementById(
        "fuelType"
    );

    /*
     * Common models seen in Sri Lanka.
     * Fuel type and model-year range are defaults for form assistance only.
     * Some imported variants can differ, therefore the user may still change fuel type/year.
     */
    const vehicleCatalog = {
        "Toyota": {
            "Corolla": { fuel: "Petrol", years: [1990, 2026] },
            "Axio": { fuel: "Petrol", years: [2006, 2022] },
            "Allion": { fuel: "Petrol", years: [2001, 2021] },
            "Premio": { fuel: "Petrol", years: [2001, 2021] },
            "Prius": { fuel: "Hybrid", years: [1997, 2026] },
            "Aqua": { fuel: "Hybrid", years: [2011, 2026] },
            "Vitz": { fuel: "Petrol", years: [1999, 2020] },
            "Yaris": { fuel: "Petrol", years: [1999, 2026] },
            "Passo": { fuel: "Petrol", years: [2004, 2023] },
            "Wigo": { fuel: "Petrol", years: [2014, 2026] },
            "Raize": { fuel: "Petrol", years: [2019, 2026] },
            "Rush": { fuel: "Petrol", years: [2006, 2026] },
            "RAV4": { fuel: "Petrol", years: [1994, 2026] },
            "Hilux": { fuel: "Diesel", years: [1990, 2026] },
            "Fortuner": { fuel: "Diesel", years: [2005, 2026] },
            "Land Cruiser": { fuel: "Diesel", years: [1990, 2026] },
            "Hiace": { fuel: "Diesel", years: [1990, 2026] }
        },

        "Honda": {
            "Civic": { fuel: "Petrol", years: [1990, 2026] },
            "City": { fuel: "Petrol", years: [1996, 2026] },
            "Fit": { fuel: "Petrol", years: [2001, 2026] },
            "Grace": { fuel: "Hybrid", years: [2014, 2020] },
            "Vezel": { fuel: "Hybrid", years: [2013, 2026] },
            "CR-V": { fuel: "Petrol", years: [1995, 2026] },
            "HR-V": { fuel: "Petrol", years: [1998, 2026] },
            "Insight": { fuel: "Hybrid", years: [1999, 2022] },
            "Freed": { fuel: "Hybrid", years: [2008, 2026] }
        },

        "Nissan": {
            "Sunny": { fuel: "Petrol", years: [1990, 2026] },
            "March": { fuel: "Petrol", years: [1992, 2022] },
            "Note": { fuel: "Petrol", years: [2005, 2026] },
            "Tiida": { fuel: "Petrol", years: [2004, 2012] },
            "Bluebird": { fuel: "Petrol", years: [1990, 2012] },
            "X-Trail": { fuel: "Petrol", years: [2000, 2026] },
            "Juke": { fuel: "Petrol", years: [2010, 2026] },
            "Leaf": { fuel: "Electric", years: [2010, 2026] },
            "Navara": { fuel: "Diesel", years: [1997, 2026] },
            "Caravan": { fuel: "Diesel", years: [1990, 2026] }
        },

        "Suzuki": {
            "Alto": { fuel: "Petrol", years: [1990, 2026] },
            "Wagon R": { fuel: "Petrol", years: [1993, 2026] },
            "Swift": { fuel: "Petrol", years: [2000, 2026] },
            "Celerio": { fuel: "Petrol", years: [2014, 2026] },
            "Baleno": { fuel: "Petrol", years: [1995, 2026] },
            "Spacia": { fuel: "Hybrid", years: [2013, 2026] },
            "Every": { fuel: "Petrol", years: [1990, 2026] },
            "Jimny": { fuel: "Petrol", years: [1990, 2026] },
            "Vitara": { fuel: "Petrol", years: [1990, 2026] },
            "S-Presso": { fuel: "Petrol", years: [2019, 2026] }
        },

        "Mitsubishi": {
            "Lancer": { fuel: "Petrol", years: [1990, 2017] },
            "Mirage": { fuel: "Petrol", years: [2012, 2026] },
            "Attrage": { fuel: "Petrol", years: [2013, 2026] },
            "Outlander": { fuel: "Petrol", years: [2001, 2026] },
            "Pajero": { fuel: "Diesel", years: [1990, 2021] },
            "Montero": { fuel: "Diesel", years: [1990, 2021] },
            "Eclipse Cross": { fuel: "Petrol", years: [2017, 2026] },
            "L200": { fuel: "Diesel", years: [1990, 2026] }
        },

        "Mazda": {
            "Mazda 2": { fuel: "Petrol", years: [2002, 2026] },
            "Mazda 3": { fuel: "Petrol", years: [2003, 2026] },
            "Mazda 6": { fuel: "Petrol", years: [2002, 2026] },
            "CX-3": { fuel: "Petrol", years: [2015, 2026] },
            "CX-5": { fuel: "Petrol", years: [2012, 2026] },
            "CX-30": { fuel: "Petrol", years: [2019, 2026] }
        },

        "Hyundai": {
            "Accent": { fuel: "Petrol", years: [1994, 2026] },
            "Elantra": { fuel: "Petrol", years: [1990, 2026] },
            "i10": { fuel: "Petrol", years: [2007, 2026] },
            "i20": { fuel: "Petrol", years: [2008, 2026] },
            "Tucson": { fuel: "Petrol", years: [2004, 2026] },
            "Santa Fe": { fuel: "Diesel", years: [2000, 2026] },
            "Kona": { fuel: "Electric", years: [2017, 2026] }
        },

        "Kia": {
            "Picanto": { fuel: "Petrol", years: [2004, 2026] },
            "Rio": { fuel: "Petrol", years: [2000, 2026] },
            "Cerato": { fuel: "Petrol", years: [2003, 2026] },
            "Sportage": { fuel: "Petrol", years: [1993, 2026] },
            "Sorento": { fuel: "Diesel", years: [2002, 2026] },
            "Sonet": { fuel: "Petrol", years: [2020, 2026] }
        },

        "Daihatsu": {
            "Mira": { fuel: "Petrol", years: [1990, 2026] },
            "Move": { fuel: "Petrol", years: [1995, 2026] },
            "Tanto": { fuel: "Petrol", years: [2003, 2026] },
            "Terios": { fuel: "Petrol", years: [1997, 2026] }
        },

        "Perodua": {
            "Axia": { fuel: "Petrol", years: [2014, 2026] },
            "Bezza": { fuel: "Petrol", years: [2016, 2026] },
            "Myvi": { fuel: "Petrol", years: [2005, 2026] },
            "Ativa": { fuel: "Petrol", years: [2021, 2026] }
        },

        "Isuzu": {
            "D-Max": { fuel: "Diesel", years: [2002, 2026] },
            "MU-X": { fuel: "Diesel", years: [2013, 2026] },
            "N-Series": { fuel: "Diesel", years: [1990, 2026] }
        },

        "Ford": {
            "Ranger": { fuel: "Diesel", years: [1998, 2026] },
            "Everest": { fuel: "Diesel", years: [2003, 2026] },
            "EcoSport": { fuel: "Petrol", years: [2013, 2022] }
        },

        "BMW": {
            "1 Series": { fuel: "Petrol", years: [2004, 2026] },
            "3 Series": { fuel: "Petrol", years: [1990, 2026] },
            "5 Series": { fuel: "Petrol", years: [1990, 2026] },
            "X1": { fuel: "Petrol", years: [2009, 2026] },
            "X3": { fuel: "Petrol", years: [2003, 2026] },
            "X5": { fuel: "Petrol", years: [1999, 2026] }
        },

        "Mercedes-Benz": {
            "A-Class": { fuel: "Petrol", years: [1997, 2026] },
            "C-Class": { fuel: "Petrol", years: [1993, 2026] },
            "E-Class": { fuel: "Diesel", years: [1993, 2026] },
            "S-Class": { fuel: "Petrol", years: [1990, 2026] },
            "GLA": { fuel: "Petrol", years: [2013, 2026] },
            "GLC": { fuel: "Petrol", years: [2015, 2026] },
            "GLE": { fuel: "Diesel", years: [2015, 2026] }
        },

        "Audi": {
            "A3": { fuel: "Petrol", years: [1996, 2026] },
            "A4": { fuel: "Petrol", years: [1994, 2026] },
            "A6": { fuel: "Petrol", years: [1994, 2026] },
            "Q3": { fuel: "Petrol", years: [2011, 2026] },
            "Q5": { fuel: "Diesel", years: [2008, 2026] },
            "Q7": { fuel: "Diesel", years: [2005, 2026] }
        },

        "Tata": {
            "Nano": { fuel: "Petrol", years: [2008, 2018] },
            "Indica": { fuel: "Diesel", years: [1998, 2018] },
            "Tiago": { fuel: "Petrol", years: [2016, 2026] },
            "Nexon": { fuel: "Petrol", years: [2017, 2026] }
        },

        "Mahindra": {
            "Scorpio": { fuel: "Diesel", years: [2002, 2026] },
            "Bolero": { fuel: "Diesel", years: [2000, 2026] },
            "XUV300": { fuel: "Diesel", years: [2019, 2026] },
            "XUV500": { fuel: "Diesel", years: [2011, 2021] }
        },

        "MG": {
            "ZS": { fuel: "Petrol", years: [2017, 2026] },
            "HS": { fuel: "Petrol", years: [2018, 2026] },
            "MG5": { fuel: "Electric", years: [2020, 2026] },
            "MG4": { fuel: "Electric", years: [2022, 2026] }
        },

        "BYD": {
            "Dolphin": { fuel: "Electric", years: [2021, 2026] },
            "Atto 3": { fuel: "Electric", years: [2022, 2026] },
            "Seal": { fuel: "Electric", years: [2022, 2026] }
        },

        "Other": {
            "Other": { fuel: "", years: [1990, 2026] }
        }
    };

    function populateVehicleModels(selectedModel = "") {

        const make = vehicleMake.value;

        vehicleModel.innerHTML =
            '<option value="">Select model</option>';

        vehicleYear.innerHTML =
            '<option value="">Select model first</option>';

        if (!make || !vehicleCatalog[make]) {
            fuelType.value = "";
            return;
        }

        Object.keys(vehicleCatalog[make]).forEach(function (model) {

            const option =
                document.createElement("option");

            option.value = model;
            option.textContent = model;

            if (model === selectedModel) {
                option.selected = true;
            }

            vehicleModel.appendChild(option);
        });

        if (selectedModel) {
            populateVehicleDetails();
        }
    }

    function populateVehicleDetails(selectedYear = "") {

        const make = vehicleMake.value;
        const model = vehicleModel.value;

        vehicleYear.innerHTML =
            '<option value="">Select year</option>';

        if (
            !make
            ||
            !model
            ||
            !vehicleCatalog[make]
            ||
            !vehicleCatalog[make][model]
        ) {
            fuelType.value = "";
            return;
        }

        const info =
            vehicleCatalog[make][model];

        if (info.fuel) {
            fuelType.value = info.fuel;
        } else {
            fuelType.value = "";
        }

        const currentYear =
            new Date().getFullYear();

        const startYear =
            Math.max(
                parseInt(info.years[0], 10),
                1950
            );

        const endYear =
            Math.min(
                parseInt(info.years[1], 10),
                currentYear
            );

        for (
            let year = endYear;
            year >= startYear;
            year--
        ) {

            const option =
                document.createElement(
                    "option"
                );

            option.value =
                String(year);

            option.textContent =
                String(year);

            if (
                String(year)
                ===
                String(selectedYear)
            ) {
                option.selected = true;
            }

            vehicleYear.appendChild(
                option
            );
        }
    }

    const oldVehicleMake =
        <?= json_encode($old["vehicle_make"]) ?>;

    const oldVehicleModel =
        <?= json_encode($old["vehicle_model"]) ?>;

    const oldVehicleYear =
        <?= json_encode($old["vehicle_year"]) ?>;

    const oldFuelType =
        <?= json_encode($old["fuel_type"]) ?>;

    if (oldVehicleMake) {

        vehicleMake.value =
            oldVehicleMake;

        populateVehicleModels(
            oldVehicleModel
        );

        if (oldVehicleModel) {

            vehicleModel.value =
                oldVehicleModel;

            populateVehicleDetails(
                oldVehicleYear
            );
        }

        // Keep user's submitted fuel type if validation failed.
        if (oldFuelType) {
            fuelType.value = oldFuelType;
        }
    }

    vehicleMake.addEventListener(
        "change",
        function () {

            populateVehicleModels();

            fuelType.value = "";
        }
    );

    vehicleModel.addEventListener(
        "change",
        function () {

            populateVehicleDetails();
        }
    );


    function updateRegistrationForm() {

        const isGarageAdmin = accountType.value === "garage_admin";
        const isVehicleOwner = accountType.value === "vehicle_owner";

        garageFields.style.display = isGarageAdmin ? "block" : "none";
        garageName.required = isGarageAdmin;
        garagePhone.required = isGarageAdmin;
        garageAddress.required = isGarageAdmin;
        garageDistrict.required = isGarageAdmin;

        vehicleOwnerGarageFields.style.display = isVehicleOwner ? "block" : "none";
        vehicleDetailsSection.style.display = isVehicleOwner ? "block" : "none";
        ownerSmsFields.style.display = isVehicleOwner ? "grid" : "none";

        registrationNumber.required = isVehicleOwner;
        vehicleMake.required = isVehicleOwner;
        vehicleModel.required = isVehicleOwner;
        vehicleYear.required = false;
        fuelType.required = false;
    }

    const vehicleImages = document.getElementById("vehicleImages");

    if (vehicleImages) {
        vehicleImages.addEventListener("change", function () {

            if (this.files.length > 3) {
                alert("You can upload a maximum of 3 vehicle images.");
                this.value = "";
            }
        });
    }

    accountType.addEventListener("change", updateRegistrationForm);
    updateRegistrationForm();
});
</script>

</body>
</html>