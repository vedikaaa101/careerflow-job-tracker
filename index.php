<?php
require_once "db_connect.php";

$statuses = ["Applied", "Interviewing", "Offer", "Rejected"];

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $companyName = trim($_POST["company_name"] ?? "");
    $jobTitle = trim($_POST["job_title"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $applicationDate = $_POST["application_date"] ?? "";
    $status = $_POST["status"] ?? "Applied";
    $notes = trim($_POST["notes"] ?? "");

    if (
        $companyName === "" ||
        $jobTitle === "" ||
        $applicationDate === "" ||
        !in_array($status, $statuses, true)
    ) {
        $message = "Please complete all required fields.";
        $messageType = "error";
    } else {
        $statement = $conn->prepare(
            "INSERT INTO job_applications 
            (company_name, job_title, location, application_date, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $statement->bind_param(
            "ssssss",
            $companyName,
            $jobTitle,
            $location,
            $applicationDate,
            $status,
            $notes
        );

        if ($statement->execute()) {
            $statement->close();
            header("Location: index.php?added=1");
            exit;
        }

        $statement->close();
        $message = "The application could not be saved. Please try again.";
        $messageType = "error";
    }
}

if (isset($_GET["added"])) {
    $message = "Job application added successfully.";
    $messageType = "success";
} elseif (isset($_GET["updated"])) {
    $message = "Job application updated successfully.";
    $messageType = "success";
} elseif (isset($_GET["deleted"])) {
    $message = "Job application deleted successfully.";
    $messageType = "success";
}

$applicationsStatement = $conn->prepare(
    "SELECT id, company_name, job_title, location, application_date, status, notes
     FROM job_applications
     ORDER BY application_date DESC, id DESC"
);
$applicationsStatement->execute();
$applications = $applicationsStatement->get_result();

$summaryStatement = $conn->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status = ?) AS applied,
            SUM(status = ?) AS interviewing,
            SUM(status = ?) AS offers
     FROM job_applications"
);
$appliedStatus = "Applied";
$interviewingStatus = "Interviewing";
$offerStatus = "Offer";
$summaryStatement->bind_param("sss", $appliedStatus, $interviewingStatus, $offerStatus);
$summaryStatement->execute();
$summary = $summaryStatement->get_result()->fetch_assoc();
$summaryStatement->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Careerflow | Job Application Tracker</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "DM Sans", sans-serif;
            background: #f4eee7;
            color: #30252d;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        /* =========================
           MAIN CONTAINER
        ========================= */

        .page {
            width: min(1400px, 94%);
            margin: auto;
            padding: 30px 0 50px;
        }

        /* =========================
           HEADER
        ========================= */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 70px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -1px;
            color: #40243c;
        }

        .logo span {
            color: #b86d83;
        }

        .top-tag {
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #806d76;
            border: 1px solid #d8c9c0;
            padding: 9px 15px;
            border-radius: 50px;
            background: rgba(255,255,255,0.35);
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            position: relative;
            margin-bottom: 55px;
            max-width: 850px;
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 11px;
            font-weight: 700;
            color: #b36b80;
            margin-bottom: 18px;
        }

        .hero h1 {
            font-family: "Playfair Display", serif;
            font-size: clamp(50px, 7vw, 92px);
            line-height: 0.98;
            letter-spacing: -4px;
            font-weight: 600;
            color: #3c2438;
            margin-bottom: 24px;
        }

        .hero h1 em {
            color: #a95f76;
            font-style: italic;
        }

        .hero p {
            font-size: 17px;
            line-height: 1.7;
            color: #766871;
            max-width: 620px;
        }

        .hero-decoration {
            position: absolute;
            right: 30px;
            top: 20px;
            width: 125px;
            height: 125px;
            border: 1px solid #cfa9b3;
            border-radius: 50%;
            opacity: 0.65;
        }

        .hero-decoration::before {
            content: "✦";
            position: absolute;
            right: 8px;
            bottom: -15px;
            font-size: 28px;
            color: #b86d83;
        }

        /* =========================
           ALERT
        ========================= */

        .alert {
            padding: 15px 18px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
        }

        .alert.success {
            background: #e4eee2;
            color: #466047;
            border: 1px solid #c7dcc5;
        }

        .alert.error {
            background: #f3dede;
            color: #824d55;
            border: 1px solid #e5c1c1;
        }

        /* =========================
           STATS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 45px;
        }

        .stat {
            background: rgba(255,255,255,0.55);
            border: 1px solid #dfd1c8;
            border-radius: 22px;
            padding: 25px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .stat:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(72, 43, 61, 0.08);
        }

        .stat-label {
            color: #8c7a82;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 13px;
        }

        .stat-number {
            font-family: "Playfair Display", serif;
            font-size: 38px;
            color: #43263e;
        }

        /* =========================
           CONTENT LAYOUT
        ========================= */

        .content {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 35px;
            align-items: start;
        }

        /* =========================
           ADD APPLICATION CARD
        ========================= */

        .add-card {
            background: #43263e;
            color: white;
            border-radius: 28px;
            padding: 30px;
            position: sticky;
            top: 25px;
            box-shadow: 0 20px 50px rgba(64, 35, 58, 0.16);
        }

        .add-card-top {
            margin-bottom: 27px;
        }

        .add-card-label {
            color: #d5a4b3;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .add-card h2 {
            font-family: "Playfair Display", serif;
            font-size: 31px;
            font-weight: 500;
        }

        .add-card-subtitle {
            color: #c8b7c1;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 7px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            color: #d9cbd2;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            border: 1px solid #62465c;
            background: #513148;
            color: white;
            padding: 13px 14px;
            border-radius: 12px;
            outline: none;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a995a2;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #d69aac;
            box-shadow: 0 0 0 3px rgba(214,154,172,0.12);
        }

        .form-group select option {
            color: #30252d;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            border: none;
            border-radius: 13px;
            padding: 14px;
            background: #d69aac;
            color: #3d2438;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .submit-btn:hover {
            background: #e2b0be;
            transform: translateY(-2px);
        }

        /* =========================
           APPLICATIONS
        ========================= */

        .applications-section {
            min-width: 0;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .section-heading h2 {
            font-family: "Playfair Display", serif;
            font-size: 34px;
            font-weight: 600;
            color: #40243c;
        }

        .section-heading p {
            font-size: 13px;
            color: #8c7a82;
        }

        .applications {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .application {
            display: grid;
            grid-template-columns: 55px minmax(180px, 1fr) 150px 120px 125px 85px;
            gap: 15px;
            align-items: center;
            background: rgba(255,255,255,0.68);
            border: 1px solid #dfd1c8;
            border-radius: 20px;
            padding: 17px;
            transition: 0.25s ease;
        }

        .application:hover {
            transform: translateX(4px);
            border-color: #caa9b4;
            box-shadow: 0 10px 28px rgba(70, 40, 58, 0.07);
        }

        .company-icon {
            width: 55px;
            height: 55px;
            border-radius: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9d8dc;
            color: #713f58;
            font-family: "Playfair Display", serif;
            font-size: 22px;
            font-weight: 600;
        }

        .job-info h3 {
            font-size: 15px;
            color: #3f2b39;
            margin-bottom: 3px;
        }

        .job-info p {
            font-size: 12px;
            color: #927f87;
        }

        .detail {
            font-size: 13px;
            color: #65565f;
        }

        .detail-label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.1px;
            color: #a08e96;
            margin-bottom: 3px;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            width: fit-content;
            padding: 7px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-applied {
            background: #eee4d6;
            color: #856340;
        }

        .status-applied .status-dot {
            background: #b28b5e;
        }

        .status-interviewing {
            background: #e6dcef;
            color: #694c80;
        }

        .status-interviewing .status-dot {
            background: #956cb4;
        }

        .status-offer {
            background: #dfeadb;
            color: #4f704b;
        }

        .status-offer .status-dot {
            background: #65915d;
        }

        .status-rejected {
            background: #f1dddd;
            color: #8a555d;
        }

        .status-rejected .status-dot {
            background: #bd727d;
        }

        /* =========================
           ACTIONS
        ========================= */

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 7px;
        }

        .action {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dfd1c8;
            background: #f8f3ee;
            color: #6c5963;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .action:hover {
            background: #e8d7dd;
            color: #542d4a;
            border-color: #cdaeb8;
        }

        .delete-form {
            display: inline;
        }

        .delete-action {
            cursor: pointer;
            font-family: inherit;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty {
            background: rgba(255,255,255,0.5);
            border: 1px dashed #cdbcb3;
            border-radius: 22px;
            padding: 65px 30px;
            text-align: center;
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 15px;
        }

        .empty h3 {
            font-family: "Playfair Display", serif;
            font-size: 25px;
            color: #4a3043;
            margin-bottom: 8px;
        }

        .empty p {
            color: #8d7c84;
            font-size: 14px;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            margin-top: 60px;
            padding-top: 25px;
            border-top: 1px solid #dfd1c8;
            color: #9a888f;
            font-size: 12px;
            text-align: center;
            letter-spacing: 0.5px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .content {
                grid-template-columns: 1fr;
            }

            .add-card {
                position: static;
            }

            .application {
                grid-template-columns: 55px 1fr 120px 110px 75px;
            }

            .application .location {
                display: none;
            }
        }

        @media (max-width: 800px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-decoration {
                display: none;
            }

            .application {
                grid-template-columns: 50px 1fr auto;
                gap: 12px;
            }

            .application .detail,
            .application .status {
                display: none;
            }

            .company-icon {
                width: 50px;
                height: 50px;
            }

            .actions {
                justify-content: flex-end;
            }
        }

        @media (max-width: 550px) {

            .page {
                width: 91%;
                padding-top: 20px;
            }

            .topbar {
                margin-bottom: 45px;
            }

            .top-tag {
                display: none;
            }

            .hero h1 {
                font-size: 49px;
                letter-spacing: -2.5px;
            }

            .hero p {
                font-size: 15px;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .stat {
                padding: 18px;
            }

            .stat-number {
                font-size: 30px;
            }

            .add-card {
                padding: 24px;
            }

            .section-heading {
                display: block;
            }

            .section-heading p {
                margin-top: 5px;
            }
        }

    </style>
</head>

<body>

<div class="page">

    <!-- HEADER -->
    <header class="topbar">

        <div class="logo">
            Career<span>flow</span>
        </div>

        <div class="top-tag">
            Job Application Journal
        </div>

    </header>


    <!-- HERO -->
    <section class="hero">

        <div class="eyebrow">
            Your career, organized
        </div>

        <h1>
            Keep track of<br>
            where you're <em>going.</em>
        </h1>

        <p>
            One place for every application, every interview,
            and every tiny step toward the job you're working for.
        </p>

        <div class="hero-decoration"></div>

    </section>


    <!-- ALERT -->
    <?php if ($message !== ""): ?>

        <div class="alert <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- STATS -->
    <section class="stats">

        <div class="stat">
            <div class="stat-label">Total applications</div>
            <div class="stat-number">
                <?php echo (int)($summary["total"] ?? 0); ?>
            </div>
        </div>

        <div class="stat">
            <div class="stat-label">Applications sent</div>
            <div class="stat-number">
                <?php echo (int)($summary["applied"] ?? 0); ?>
            </div>
        </div>

        <div class="stat">
            <div class="stat-label">Interviews</div>
            <div class="stat-number">
                <?php echo (int)($summary["interviewing"] ?? 0); ?>
            </div>
        </div>

        <div class="stat">
            <div class="stat-label">Offers</div>
            <div class="stat-number">
                <?php echo (int)($summary["offers"] ?? 0); ?>
            </div>
        </div>

    </section>


    <!-- MAIN CONTENT -->
    <main class="content">


        <!-- ADD APPLICATION -->
        <aside class="add-card">

            <div class="add-card-top">

                <div class="add-card-label">
                    New opportunity
                </div>

                <h2>Add an application</h2>

                <p class="add-card-subtitle">
                    Future-you will thank you for keeping this organized.
                </p>

            </div>


            <form method="POST" action="index.php">

                <div class="form-group">

                    <label for="company_name">
                        Company *
                    </label>

                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        placeholder="e.g. Microsoft"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="job_title">
                        Job title *
                    </label>

                    <input
                        type="text"
                        id="job_title"
                        name="job_title"
                        placeholder="e.g. Product Intern"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="location">
                        Location
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        placeholder="e.g. Mumbai / Remote"
                    >

                </div>


                <div class="form-group">

                    <label for="application_date">
                        Application date *
                    </label>

                    <input
                        type="date"
                        id="application_date"
                        name="application_date"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <?php foreach ($statuses as $statusOption): ?>

                            <option value="<?php echo htmlspecialchars($statusOption); ?>">
                                <?php echo htmlspecialchars($statusOption); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="notes">
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        placeholder="Anything worth remembering..."
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    + Add application
                </button>

            </form>

        </aside>


        <!-- APPLICATION LIST -->
        <section class="applications-section">

            <div class="section-heading">

                <div>
                    <h2>Your applications</h2>

                    <p>
                        A little record of where you've been knocking.
                    </p>
                </div>

            </div>


            <div class="applications">

                <?php if ($applications && $applications->num_rows > 0): ?>

                    <?php while ($application = $applications->fetch_assoc()): ?>

                        <?php
                            $companyName = $application["company_name"];
                            $firstLetter = strtoupper(substr($companyName, 0, 1));

                            $statusClass = strtolower(
                                $application["status"]
                            );

                            $formattedDate = date(
                                "d M Y",
                                strtotime($application["application_date"])
                            );
                        ?>


                        <article class="application">


                            <!-- COMPANY ICON -->
                            <div class="company-icon">
                                <?php echo htmlspecialchars($firstLetter); ?>
                            </div>


                            <!-- JOB INFORMATION -->
                            <div class="job-info">

                                <h3>
                                    <?php echo htmlspecialchars($application["job_title"]); ?>
                                </h3>

                                <p>
                                    <?php echo htmlspecialchars($companyName); ?>
                                </p>

                            </div>


                            <!-- LOCATION -->
                            <div class="detail location">

                                <span class="detail-label">
                                    Location
                                </span>

                                <?php
                                    echo $application["location"]
                                        ? htmlspecialchars($application["location"])
                                        : "Not specified";
                                ?>

                            </div>


                            <!-- DATE -->
                            <div class="detail">

                                <span class="detail-label">
                                    Applied
                                </span>

                                <?php echo $formattedDate; ?>

                            </div>


                            <!-- STATUS -->
                            <div>

                                <span class="status status-<?php echo $statusClass; ?>">

                                    <span class="status-dot"></span>

                                    <?php echo htmlspecialchars($application["status"]); ?>

                                </span>

                            </div>


                            <!-- ACTIONS -->
                            <div class="actions">

                                <a
                                    class="action"
                                    href="edit_application.php?id=<?php echo (int)$application["id"]; ?>"
                                    title="Edit"
                                >
                                    ✎
                                </a>

                                <form
                                    class="delete-form"
                                    action="delete_application.php"
                                    method="post"
                                    onsubmit="return confirm('Delete this application?');"
                                >
                                    <input type="hidden" name="id" value="<?php echo (int)$application["id"]; ?>">
                                    <button class="action delete-action" type="submit" title="Delete">×</button>
                                </form>

                            </div>

                        </article>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty">

                        <div class="empty-icon">
                            ✦
                        </div>

                        <h3>
                            Nothing here yet.
                        </h3>

                        <p>
                            Your next opportunity is waiting to be added.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>


    <!-- FOOTER -->
    <footer>
        Careerflow · Keep going, one application at a time ✦
    </footer>

</div>

</body>
</html>
