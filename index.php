<?php require 'config.php'; header('Location: ' . (empty($_SESSION['user']) ? 'login.php' : 'dashboard.php'));
