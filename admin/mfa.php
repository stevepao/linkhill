<?php
/**
 * mfa.php — Redirect to MFA/security tab.
 * Project: linkhill
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2026 Hillwork, LLC
 */
declare(strict_types=1);
header('Location: /admin/security/?tab=totp', true, 302);
exit;
