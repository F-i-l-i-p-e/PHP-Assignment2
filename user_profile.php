<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$errorMessage = "";
$updateStatus = "";

function getUserInfo($conn) {
    $loggedInUsername = $_SESSION['username'];
    $sql = "SELECT nickname, profile_picture FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error: " . $conn->error);
    }

    $stmt->bind_param('s', $loggedInUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $userInfo = $result->fetch_assoc();
    $stmt->close();
    return $userInfo;
}

// Handle logout request
if (isset($_POST['logout'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "phpa2";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update the logged_in column to 0 for the current user
    $loggedInUsername = $_SESSION['username'];
    $sql = "UPDATE users SET logged_in=0 WHERE username=?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error: " . $conn->error);
    }

    $stmt->bind_param('s', $loggedInUsername);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // Redirect to the login page with a success message
    header("Location: login.php?logout=success");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "phpa2";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $loggedInUsername = $_SESSION['username'];
    $nickname = $_POST['nickname'];
    $newPassword = $_POST['new_password'];

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
        $target_dir = "profile_pics/";
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $target_file;
        } else {
            $errorMessage = "Error uploading the image.";
        }
    } else {
        $profile_picture = isset($userInfo['profile_picture']) ? $userInfo['profile_picture'] : '';
    }

    // Prepare and bind the SQL statement
    if (!empty($newPassword)) {
        $sql = "UPDATE users SET nickname=?, profile_picture=?, password=? WHERE username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssss', $nickname, $profile_picture, $newPassword, $loggedInUsername);
    } else {
        $sql = "UPDATE users SET nickname=?, profile_picture=? WHERE username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $nickname, $profile_picture, $loggedInUsername);
    }

    if ($stmt->execute()) {
        $updateStatus = "Profile updated successfully!";
        // Fetch updated user info after updating the profile
        $userInfo = getUserInfo($conn);
    } else {
        $errorMessage = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "phpa2";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $userInfo = getUserInfo($conn);
    $conn->close();
}

?>
<?php
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="user_profile.php">Profile</a>
        <a href="user_profile.php?logout=true">Logout</a>
        <a href="view_content.php">View Content</a>
    </nav>

    <?php
    if (!empty($errorMessage)) {
        echo "<p style='color: red;'>" . $errorMessage . "</p>";
    }

    if (!empty($updateStatus)) {
        echo "<p style='color: green;'>" . $updateStatus . "</p>";
    }
?>

    
        <div>
            <br>
            <h2>Profile</h2>
            <h3>Nickname: <?php echo isset($userInfo['nickname']) ? htmlspecialchars($userInfo['nickname']) : 'None'; ?></h3>
            <h3>Profile Picture:</h3>
            <?php
            if (isset($userInfo['profile_picture']) && !empty($userInfo['profile_picture'])) {
                echo '<img src="' . htmlspecialchars($userInfo['profile_picture']) . '" alt="Profile Picture" width="100">';
            } else {
                echo '<p>None</p>';
            }
            ?>
            <br><br>
            <form action="user_profile.php" method="POST" enctype="multipart/form-data">
                <label for="nickname">Nickname:</label>
                <input type="text" name="nickname" id="nickname" value="<?php echo isset($userInfo['nickname']) ? htmlspecialchars($userInfo['nickname']) : ''; ?>" required>
                <br>
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password">
                <br>
                <label for="profile_picture">Upload Profile Picture:</label>
                <input type="file" name="profile_picture" id="profile_picture">
                <br>
                <input type="submit" name="update_profile" value="Update Profile">
            </form>
        </div>
    </body>
    </html>
    