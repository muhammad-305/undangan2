<?php
requireLogin();

// Destroy session
session_destroy();

redirect('');
?>
