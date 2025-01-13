<?php
session_start();
session_destroy(); // Destroy the session

// Redirect to the login page
header('Location: ../index.html'); // Adjust path if necessary
exit();
?>
