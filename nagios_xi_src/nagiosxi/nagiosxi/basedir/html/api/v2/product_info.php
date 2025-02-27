<?php

http_response_code(404);
$response = ['error' => _('API endpoints missing. If you are upgrading from 2024R1.1.3 or earlier, please manually refresh this page.')];
echo json_encode($response);
