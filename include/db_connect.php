
<?php
$conn = mysqli_connect("localhost:3307", "root", "", "corridor_communique");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>