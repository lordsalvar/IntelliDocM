<?php
// Include database connection
include '/main/IntelliDocM/database.php';

// Start session
session_start();

// Initialize variables
$verificationResult = null;
$errorMessage = null;

// Check if proposal_id and moderator_name are provided in the URL
if (isset($_GET['proposal_id']) && isset($_GET['moderator_name'])) {
    $proposal_id = (int)$_GET['proposal_id'];
    $moderator_name = htmlspecialchars($_GET['moderator_name']);

    // Verify the proposal in the database
    $sql = "SELECT * FROM activity_proposals WHERE proposal_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $proposal = $result->fetch_assoc();
        $verificationResult = [
            'Proposal ID' => $proposal['proposal_id'],
            'Activity Title' => $proposal['activity_title'],
            'Moderator Name' => $moderator_name,
            'Activity Date' => $proposal['activity_date'],
            'Venue' => $proposal['venue'],
        ];
    } else {
        $errorMessage = "Invalid or unrecognized QR code.";
    }
} else {
    $errorMessage = "Invalid QR code data.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator QR Code Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="text-center">
            <h1 class="my-4">Moderator QR Code Verification</h1>
        </div>

        <?php if ($verificationResult): ?>
            <div class="alert alert-success text-center" role="alert">
                <strong>QR Code Verified Successfully!</strong>
            </div>
            <div class="card">
                <div class="card-header text-white bg-primary">
                    Verification Details
                </div>
                <div class="card-body">
                    <?php foreach ($verificationResult as $key => $value): ?>
                        <p><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($errorMessage): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="/" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>