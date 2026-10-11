<?php
return ['access_code' => env('ESIM_ACCESS_CODE'), 'webhook_ips' => array_values(array_filter(array_map('trim', explode(',', env('ESIM_WEBHOOK_IPS', '3.1.131.226,54.254.74.88,18.136.190.97,18.136.60.197,18.136.19.137,54.151.164.206,52.76.129.149,18.142.83.98'))))), 'base_url' => 'https://api.esimaccess.com/api/v1/open'];
