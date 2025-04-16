<?php

namespace App\Helpers;

class Helper
{
    /**
     * Convert an array to a JSON string with special characters escaped.
     *
     * @param array $array
     * @return string
     */
    public static function JSON($array)
    {
        $json = array();

        $array = json_decode(json_encode($array), true);

        foreach ($array as $key => $val) {
            $json[$key] = str_replace(array("\r", "\n", "\r\n"), "", $val);
        }

        return addslashes(json_encode($json));
    }

    /**
     * Convert a date string to a Unix timestamp.
     *
     * @param string $date
     * @return int
     */
    public static function timestampFromDate($date)
    {
        $unixtime   = 0;
        $hr         = date('H', strtotime($date));
        $min        = date('i', strtotime($date));
        $sec        = date('s', strtotime($date));
        $mon        = date('m', strtotime($date));
        $day        = date('d', strtotime($date));
        $yr         = date('Y', strtotime($date));
        $dt         = localtime($unixtime, true);

        $unixnewtime = mktime(
            $dt['tm_hour'] + $hr - 2,
            $dt['tm_min'] + $min,
            $dt['tm_sec'] + $sec,
            $dt['tm_mon'] + $mon,
            $dt['tm_mday'] + $day - 1,
            $dt['tm_year'] + $yr - 70
        );

        return $unixnewtime;
    }

    /**
     * Get an access token for Firebase Cloud Messaging.
     *
     * @return string|null
     * @throws \Exception
     */
    public static function getAccessToken()
    {
        $fcmFile  = public_path('fcm-key/kloader-13f41-firebase-adminsdk-6945q-3a2fa8dc98.json');
        $serviceAccount = file_get_contents($fcmFile);
        $credentials = json_decode($serviceAccount, true);

        $jwtHeader = self::base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ]));

        $now = time();
        $jwtClaimSet = self::base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]));

        $unsignedToken = $jwtHeader . '.' . $jwtClaimSet;

        $privateKey = $credentials['private_key'];

        openssl_sign($unsignedToken, $signature, $privateKey, 'sha256WithRSAEncryption');
        $jwtSignature = self::base64UrlEncode($signature);

        // Complete the JWT token
        $jwt = $unsignedToken . '.' . $jwtSignature;

        // Make the POST request to get the access token
        $postFields = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        // cURL request to Google's OAuth 2.0 token endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseDecoded = json_decode($response, true);

        // Check if the access token is available
        if (isset($responseDecoded['access_token'])) {
            return $responseDecoded['access_token'];
        } else {
            //throw new Exception('Error getting access token: ' . $response);
        }
    }


    /**
     * Encode data in base64 URL-safe format.
     *
     * @param string $data
     * @return string
     */
    public static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Send a push notification using Firebase Cloud Messaging.
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param int $type
     * @return string|false
     */
    public static function sendPushNotification($deviceToken, $title, $body, $type = 0)
    {

        $accessToken = self::getAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/kloader-13f41/messages:send";

        $notificationData = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'type' => (string) $type ,
                     'message' => $title
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    /**
     * Send a push notification using the old method.
     * @deprecated 0.1
     * @param array $json_data
     * @return void
     */
    public static function sendPushNotificationOld($json_data)
    {

        $data = json_encode($json_data);
        $url = 'https://fcm.googleapis.com/fcm/send';

        $server_key = config('services.fcm.server_key');

        $headers = array(
            'Content-Type:application/json',
            'Authorization:key=' . $server_key
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);

        if ($result === FALSE) {
            //die('Oops! FCM Send Error: ' . curl_error($ch));
        } else {
            //echo "notification sent";
        }
        curl_close($ch);
    }

    /**
     * Get a list of number plates.
     *
     * @return array
     */
    public static function numberPlates()
    {

        $numberPlates = ['BD51-SMR', 'PZ65-PWO', 'PL8TE', 'GF57-XWD', 'TE44-ANT', 'NU74'];
        return $numberPlates;
    }
}
