<?php
require_once "db_connect.php";

$statuses = ["Applied", "Interviewing", "Offer", "Rejected"];
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$statement = $conn->prepare("SELECT * FROM job_applications WHERE id = ?");
$statement->bind_param("i", $id);
$statement->execute();
$application = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$application) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $companyName = trim($_POST["company_name"] ?? "");
    $jobTitle = trim($_POST["job_title"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $applicationDate = $_POST["application_date"] ?? "";
    $status = $_POST["status"] ?? "Applied";
    $notes = trim($_POST["notes"] ?? "");

    if ($companyName === "" || $jobTitle === "" || $applicationDate === "" || !in_array($status, $statuses, true)) {
        $error = "Please complete all required fields.";
    } else {
        $statement = $conn->prepare(
            "UPDATE job_applications
             SET company_name = ?, job_title = ?, location = ?, application_date = ?, status = ?, notes = ?
             WHERE id = ?"
        );
        $statement->bind_param("ssssssi", $companyName, $jobTitle, $location, $applicationDate, $status, $notes, $id);
        $statement->execute();
        $statement->close();
        header("Location: index.php?updated=1");
        exit;
    }

    $application = [
        "company_name" => $companyName, "job_title" => $jobTitle, "location" => $location,
        "application_date" => $applicationDate, "status" => $status, "notes" => $notes
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit application | Job Application Tracker</title>
    <style>
        * { box-sizing: border-box; } body { background:#f4f7fb; color:#172033; font-family:Arial,sans-serif; margin:0; }
        main { margin:42px auto; max-width:720px; padding:0 20px; } .card { background:#fff; border-radius:12px; box-shadow:0 5px 18px #1b294018; padding:24px; }
        .grid { display:grid; gap:16px; grid-template-columns:repeat(2,1fr); } label { display:block; font-size:14px; font-weight:bold; margin-bottom:6px; }
        input, select, textarea { border:1px solid #cbd5e1; border-radius:7px; font:inherit; padding:10px; width:100%; } textarea { min-height:90px; resize:vertical; }
        .full { grid-column:1/-1; } button,.back { background:#2563eb; border:0; border-radius:7px; color:white; cursor:pointer; display:inline-block; font:inherit; font-weight:bold; margin-top:18px; padding:11px 18px; text-decoration:none; }
        .back { background:#64748b; margin-left:8px; } .error { background:#fee2e2; border-radius:7px; color:#991b1b; padding:12px; } @media(max-width:600px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
    </style>
</head>
<body>
    <main><div class="card">
        <h1>Edit application</h1>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <div class="grid">
                <div><label for="company_name">Company name *</label><input id="company_name" name="company_name" required value="<?= htmlspecialchars($application["company_name"]) ?>"></div>
                <div><label for="job_title">Job title *</label><input id="job_title" name="job_title" required value="<?= htmlspecialchars($application["job_title"]) ?>"></div>
                <div><label for="location">Location</label><input id="location" name="location" value="<?= htmlspecialchars($application["location"]) ?>"></div>
                <div><label for="application_date">Application date *</label><input id="application_date" name="application_date" type="date" required value="<?= htmlspecialchars($application["application_date"]) ?>"></div>
                <div><label for="status">Status *</label><select id="status" name="status"><?php foreach ($statuses as $status): ?><option value="<?= $status ?>" <?= $application["status"] === $status ? "selected" : "" ?>><?= $status ?></option><?php endforeach; ?></select></div>
                <div class="full"><label for="notes">Notes</label><textarea id="notes" name="notes"><?= htmlspecialchars($application["notes"]) ?></textarea></div>
            </div>
            <button type="submit">Save changes</button><a class="back" href="index.php">Cancel</a>
        </form>
    </div></main>
</body>
</html>
