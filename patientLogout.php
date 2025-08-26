<?php
session_start();
// Destroy all session data for patient
session_unset();
session_destroy();
// No redirect, just end session for AJAX
http_response_code(200);
exit();
