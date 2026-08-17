<?php

require_once 'auth.php';

$activePage = "profile";

$message = "";
$success = "";


// =====================================================
// SAVE PROFILE
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $garageName =
        trim($_POST["garage_name"] ?? "");

    $mobileNumber =
        trim($_POST["mobile_number"] ?? "");

    $address =
        trim($_POST["address"] ?? "");

    $city =
        trim($_POST["city"] ?? "");

    $district =
        trim($_POST["district"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $openingTime =
        $_POST["opening_time"] ?? null;

    $closingTime =
        $_POST["closing_time"] ?? null;


    if (
        $garageName === ""
        ||
        $mobileNumber === ""
        ||
        $address === ""
    ) {

        $message =
            "Please complete the required fields.";

    } else {

        $logoName =
            $garage["image"] ?? null;


        // =====================================================
        // LOGO UPLOAD
        // =====================================================

        if (
            isset($_FILES["garage_logo"])
            &&
            $_FILES["garage_logo"]["error"] === UPLOAD_ERR_OK
        ) {

            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];


            $fileType =
                mime_content_type(
                    $_FILES["garage_logo"]["tmp_name"]
                );


            if (
                !in_array(
                    $fileType,
                    $allowedTypes,
                    true
                )
            ) {

                $message =
                    "Only JPG, PNG or WEBP images are allowed.";

            } else {

                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES["garage_logo"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                $logoName =
                    "garage_"
                    . $garageId
                    . "_"
                    . time()
                    . "."
                    . $extension;


                $uploadFolder =
                    __DIR__
                    . "/../uploads/garages/";


                if (!is_dir($uploadFolder)) {

                    mkdir(
                        $uploadFolder,
                        0777,
                        true
                    );
                }


                move_uploaded_file(
                    $_FILES["garage_logo"]["tmp_name"],
                    $uploadFolder . $logoName
                );
            }
        }


        if ($message === "") {

            $sql = "
                UPDATE garages

                SET
                    garage_name = ?,
                    mobile_number = ?,
                    address = ?,
                    city = ?,
                    district = ?,
                    description = ?,
                    opening_time = ?,
                    closing_time = ?,
                    image = ?

                WHERE garage_id = ?
            ";


            $stmt =
                mysqli_prepare(
                    $conn,
                    $sql
                );


            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssi",
                $garageName,
                $mobileNumber,
                $address,
                $city,
                $district,
                $description,
                $openingTime,
                $closingTime,
                $logoName,
                $garageId
            );


            if (
                mysqli_stmt_execute(
                    $stmt
                )
            ) {

                $success =
                    "Garage profile updated successfully.";


                // Reload garage data

                $reloadSql = "
                    SELECT *
                    FROM garages
                    WHERE garage_id = ?
                    LIMIT 1
                ";


                $reloadStmt =
                    mysqli_prepare(
                        $conn,
                        $reloadSql
                    );


                mysqli_stmt_bind_param(
                    $reloadStmt,
                    "i",
                    $garageId
                );


                mysqli_stmt_execute(
                    $reloadStmt
                );


                $reloadResult =
                    mysqli_stmt_get_result(
                        $reloadStmt
                    );


                $garage =
                    mysqli_fetch_assoc(
                        $reloadResult
                    );

            } else {

                $message =
                    "Unable to update garage profile.";
            }
        }
    }
}

?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Garage Profile - AutoTrack
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../css/garage-admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/dashboard-layout.css"
    >

</head>


<body>

<div class="app-shell">


    <?php
    require_once
        '../includes/garage-sidebar.php';
    ?>


    <main class="main">


        <div class="page-header">

            <div>

                <h1>
                    Garage Profile
                </h1>

                <p>
                    Manage your garage
                    information and logo.
                </p>

            </div>

        </div>


        <?php if ($message): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars(
                    $success
                );
                ?>

            </div>

        <?php endif; ?>


        <div class="card">

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="form-grid">


                    <div class="field full">

                        <label>
                            Garage Logo
                        </label>


                        <?php if (
                            !empty(
                                $garage["image"]
                            )
                        ): ?>

                            <img
                                src="../uploads/garages/<?php
                                echo htmlspecialchars(
                                    $garage["image"]
                                );
                                ?>"
                                style="
                                    width:100px;
                                    height:100px;
                                    object-fit:cover;
                                    border-radius:16px;
                                    margin-bottom:12px;
                                "
                            >

                        <?php endif; ?>


                        <input
                            type="file"
                            name="garage_logo"
                            accept="image/jpeg,image/png,image/webp"
                        >

                    </div>


                    <div class="field">

                        <label>
                            Garage Name
                        </label>

                        <input
                            type="text"
                            name="garage_name"
                            value="<?php
                            echo htmlspecialchars(
                                $garage["garage_name"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            Mobile Number
                        </label>

                        <input
                            type="text"
                            name="mobile_number"
                            value="<?php
                            echo htmlspecialchars(
                                $garage["mobile_number"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="field full">

                        <label>
                            Address
                        </label>

                        <input
                            type="text"
                            name="address"
                            value="<?php
                            echo htmlspecialchars(
                                $garage["address"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="field">

                        <label>
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            value="<?php
                            echo htmlspecialchars(
                                $garage["city"] ?? ""
                            );
                            ?>"
                        >

                    </div>


                    <div class="field">

                        <label>
                            District
                        </label>

                        <select
                            name="district"
                        >

                            <?php

                            $districts = [
                                "Jaffna",
                                "Kilinochchi",
                                "Mullaitivu",
                                "Mannar",
                                "Vavuniya"
                            ];

                            foreach (
                                $districts
                                as $district
                            ):

                            ?>

                                <option
                                    value="<?php
                                    echo $district;
                                    ?>"
                                    <?php
                                    echo
                                    ($garage["district"] ?? "")
                                    === $district
                                    ? "selected"
                                    : "";
                                    ?>
                                >

                                    <?php
                                    echo $district;
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="field">

                        <label>
                            Opening Time
                        </label>

                        <input
                            type="time"
                            name="opening_time"
                            value="<?php
                            echo htmlspecialchars(
                                $garage[
                                    "opening_time"
                                ] ?? ""
                            );
                            ?>"
                        >

                    </div>


                    <div class="field">

                        <label>
                            Closing Time
                        </label>

                        <input
                            type="time"
                            name="closing_time"
                            value="<?php
                            echo htmlspecialchars(
                                $garage[
                                    "closing_time"
                                ] ?? ""
                            );
                            ?>"
                        >

                    </div>


                    <div class="field full">

                        <label>
                            Garage Description
                        </label>

                        <textarea
                            name="description"
                        ><?php
                        echo htmlspecialchars(
                            $garage[
                                "description"
                            ] ?? ""
                        );
                        ?></textarea>

                    </div>


                    <div class="full">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Save Garage Profile
                        </button>

                    </div>


                </div>

            </form>

        </div>


    </main>

</div>

</body>

</html>