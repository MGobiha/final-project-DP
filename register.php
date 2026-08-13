<?php
session_start();
require_once 'config/database.php';

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
    "garage_description" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accountType = trim($_POST["account_type"] ?? "");
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobileNumber = trim($_POST["mobile_number"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $maintenanceSms = isset($_POST["maintenance_sms"]) ? 1 : 0;
    $appointmentSms = isset($_POST["appointment_sms"]) ? 1 : 0;
    $newsSms = isset($_POST["news_sms"]) ? 1 : 0;

    $garageName = trim($_POST["garage_name"] ?? "");
    $garagePhone = trim($_POST["garage_phone"] ?? "");
    $garageAddress = trim($_POST["garage_address"] ?? "");
    $garageDistrict = trim($_POST["garage_district"] ?? "");
    $garageDescription = trim($_POST["garage_description"] ?? "");

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
        "garage_description" => $garageDescription
    ];

    $allowedTypes = ["vehicle_owner", "garage_admin"];
    $allowedDistricts = ["Jaffna", "Kilinochchi", "Mullaitivu", "Mannar", "Vavuniya"];

    if (!in_array($accountType, $allowedTypes, true)) {
        $message = "Please select a valid account type.";
    } elseif ($firstName === "" || $lastName === "" || $email === "" || $mobileNumber === "" || $password === "" || $confirmPassword === "") {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
    } elseif ($accountType === "garage_admin" && ($garageName === "" || $garagePhone === "" || $garageAddress === "" || $garageDistrict === "")) {
        $message = "Please complete all required garage information.";
    } elseif ($accountType === "garage_admin" && !in_array($garageDistrict, $allowedDistricts, true)) {
        $message = "Please select a valid Northern Province district.";
    } else {
        $checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);

        if (!$checkStmt) {
            $message = "Unable to validate the email address.";
        } else {
            mysqli_stmt_bind_param($checkStmt, "s", $email);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if (mysqli_num_rows($checkResult) > 0) {
                $message = "An account already exists with this email address.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                if ($accountType === "vehicle_owner") {
                    $role = "vehicle_owner";
                    $sql = "
                        INSERT INTO users
                        (first_name, last_name, email, mobile_number, password, maintenance_sms, appointment_sms, news_sms, role)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    $stmt = mysqli_prepare($conn, $sql);

                    if (!$stmt) {
                        $message = "Unable to prepare registration.";
                    } else {
                        mysqli_stmt_bind_param(
                            $stmt,
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

                        if (mysqli_stmt_execute($stmt)) {
                            $_SESSION["success_message"] = "Registration successful. Please sign in.";
                            header("Location: login.php");
                            exit();
                        } else {
                            $message = "Registration failed: " . mysqli_stmt_error($stmt);
                        }
                    }
                }

                if ($accountType === "garage_admin") {
                    mysqli_begin_transaction($conn);

                    try {
                        $role = "garage_admin";
                        $userSql = "
                            INSERT INTO users
                            (first_name, last_name, email, mobile_number, password, maintenance_sms, appointment_sms, news_sms, role)
                            VALUES (?, ?, ?, ?, ?, 0, 0, 0, ?)
                        ";
                        $userStmt = mysqli_prepare($conn, $userSql);

                        if (!$userStmt) {
                            throw new Exception("Unable to prepare garage administrator account.");
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

                        if (!mysqli_stmt_execute($userStmt)) {
                            throw new Exception("Unable to create garage administrator account.");
                        }

                        $garageAdminUserId = mysqli_insert_id($conn);
                        $ownerName = trim($firstName . " " . $lastName);

                        $garageSql = "
                            INSERT INTO garages
                            (owner_user_id, garage_name, owner_name, email, mobile_number, address, district, description, approval_status, active_status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1)
                        ";
                        $garageStmt = mysqli_prepare($conn, $garageSql);

                        if (!$garageStmt) {
                            throw new Exception("Unable to prepare garage registration.");
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

                        if (!mysqli_stmt_execute($garageStmt)) {
                            throw new Exception("Unable to create garage record.");
                        }

                        mysqli_commit($conn);
                        $_SESSION["success_message"] = "Garage registration submitted successfully. Your garage is waiting for System Admin approval.";
                        header("Location: login.php");
                        exit();
                    } catch (Throwable $e) {
                        mysqli_rollback($conn);
                        $message = "Garage registration failed. " . $e->getMessage();
                    }
                }
            }
        }
    }
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
            grid-template-columns: minmax(0, 1fr) minmax(500px, 0.78fr);
        }

        .register-visual {
            padding: 64px;
            display: flex;
            align-items: center;
            background: radial-gradient(circle at top right, rgba(255,255,255,.16), transparent 35%), linear-gradient(145deg, #0f62fe, #0b3d91);
            color: #fff;
        }

        .register-visual-inner { max-width: 560px; margin: 0 auto; }
        .register-brand { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 44px; font-size: 22px; font-weight: 800; }
        .register-brand-badge { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; background: #fff; color: #0f62fe; }
        .register-visual h1 { margin: 0 0 18px; font-size: clamp(38px, 4vw, 58px); line-height: 1.06; letter-spacing: -1.5px; }
        .register-visual p { margin: 0; color: rgba(255,255,255,.86); font-size: 17px; line-height: 1.8; }
        .register-features { margin-top: 34px; display: grid; gap: 13px; }
        .register-feature { display: flex; gap: 10px; align-items: flex-start; color: rgba(255,255,255,.92); }

        .register-panel {
            padding: 34px 34px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-y: auto;
            background: #fff;
        }

        .register-card { width: 100%; max-width: 650px; }
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
            .form-grid-2, .garage-fields-grid { grid-template-columns: 1fr; }
            .register-panel { padding: 24px 16px; }
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

            <form method="POST">
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
    const garageName = document.getElementById("garageName");
    const garagePhone = document.getElementById("garagePhone");
    const garageAddress = document.getElementById("garageAddress");
    const garageDistrict = document.getElementById("garageDistrict");

    function updateRegistrationForm() {
        const isGarageAdmin = accountType.value === "garage_admin";
        garageFields.style.display = isGarageAdmin ? "block" : "none";
        ownerSmsFields.style.display = isGarageAdmin ? "none" : "grid";
        garageName.required = isGarageAdmin;
        garagePhone.required = isGarageAdmin;
        garageAddress.required = isGarageAdmin;
        garageDistrict.required = isGarageAdmin;
    }

    accountType.addEventListener("change", updateRegistrationForm);
    updateRegistrationForm();
});
</script>

</body>
</html>