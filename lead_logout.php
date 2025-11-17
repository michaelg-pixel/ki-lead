<?php
/**
 * Lead Logout
 */

session_start();
session_destroy();

header('Location: lead_register.php');
exit;
