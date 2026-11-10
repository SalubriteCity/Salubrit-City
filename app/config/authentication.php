<?php

return [
    'access_token_ttl' => (int) env('AUTH_ACCESS_TOKEN_TTL', 15),
    'refresh_token_ttl' => (int) env('AUTH_REFRESH_TOKEN_TTL', 43200),
    'email_otp_ttl' => (int) env('AUTH_EMAIL_OTP_TTL', 10),
];
