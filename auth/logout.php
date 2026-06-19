<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/includes/auth.php';

logoutUser();
redirectTo('auth/login.php');
