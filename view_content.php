<?php
session_start();
$loggedIn = isset($_SESSION['username']);

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add']) && $loggedIn) {
        $band_name = $_POST['band_name'];
        $description = $_POST['description'];

        // Handle file upload
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo = $target_file;
            $sql = "INSERT INTO bands (band_name, description, photo) VALUES ('$band_name', '$description', '$photo')";
            $conn->query($sql);
        } else {
            echo "Error uploading the image.";
        }
    } elseif (isset($_POST['edit']) && $loggedIn) {
        $id = $_POST['id'];
        $band_name = $_POST['band_name'];
        $description = $_POST['description'];
        $photo = $_POST['photo'];

        $sql = "UPDATE bands SET band_name='$band_name', description='$description', photo='$photo' WHERE id='$id'";
        $conn->query($sql);
    } elseif (isset($_POST['delete']) && $loggedIn) {
        $id = $_POST['id'];
        $sql = "DELETE FROM bands WHERE id = '$id'";
        $conn->query($sql);
    }
}

$sql = "SELECT * FROM bands";
$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Content</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function editBand(id, band_name, description, photo) {
            document.getElementById("editForm").style.display = "block";
            document.getElementById("edit_id").value = id;
            document.getElementById("edit_band_name").value = band_name;
            document.getElementById("edit_description").value = description;
            document.getElementById("edit_photo").value = photo;
        }
    </script>
    <body>
    <nav>
        <?php
        if ($loggedIn) {
            echo '<a href="user_profile.php">Profile</a>';
            echo '<a href="user_profile.php?logout=true">Logout</a>';
        } else {
            echo '<a href="login.php">Login</a>';
            echo '<a href="register.php">Register</a>';
        }
        ?>
        <a href="view_content.php">View Content</a>
    </nav>
    <h1>View Content</h1>

<?php if ($loggedIn) { ?>
<form action="view_content.php" method="POST" enctype="multipart/form-data">
    <label for="add_contant" class=addcontent>ADD CONTENT</label>
    <label for="band_name">Band Name:</label>
    <input type="text" name="band_name" id="band_name" required>
    <br>
    <label for="description">Description:</label>
    <textarea name="description" id="description" required></textarea>
    <br>
    <label for="photo">Photo:</label>
    <input type="file" name="photo" id="photo" required>
    <br>
    <br>
    <input type="submit" name="add" value="Add Band">
</form>

<form id="editForm" action="view_content.php" method="POST" style="display: none;">
    <input type="hidden" name="id" id="edit_id">
    <label for="edit_band_name">Band Name:</label>
    <input type="text" name="band_name" id="edit_band_name" required>
    <br>
    <label for="edit_description">Description:</label>
    <textarea name="description" id="edit_description" required></textarea>
    <br>
    <label for="edit_photo">Photo URL:</label>
    <input type="text" name="photo" id="edit_photo" required>
    <br>
    <br>
    <input type="submit" name="edit" value="Edit Band">
</form>
<?php } ?>

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div>";
        echo "<h2>" . $row['band_name'] . "</h2>";
        echo "<p>" . $row['description'] . "</p>";
        echo "<img src='" . $row['photo'] . "' alt='" . $row['band_name'] . "' width='600'>";
        if ($loggedIn) {
            echo "<button onclick=\"editBand('" . $row['id'] . "','" . addslashes($row['band_name']) . "','" . addslashes($row['description']) . "','" . $row['photo'] . "')\">Edit</button>";
            echo "<form action='view_content.php' method='POST'>";
            echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
            echo "<input type='submit' name='delete' value='Delete'>";
            echo "</form>";
        }
        echo "</div>";
    }
} else {
    echo "No bands found.";
}
$conn->close();
?>
</body>
</html>