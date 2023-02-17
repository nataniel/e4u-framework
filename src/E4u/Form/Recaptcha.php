<?php
namespace E4u\Form;

class Recaptcha
{
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const FIELD_NAME = 'g-recaptcha-response';

    private $sitekey;
    private $secret;

    public function __construct(string $secret, ?string $sitekey = null)
    {
        $this->sitekey = $sitekey;
        $this->secret = $secret;
    }

    public function verifyResponse($response): Recaptcha\Response
    {
        $result = $this->httpPost(self::VERIFY_URL, [
            'secret' => $this->secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ]);

        return new Recaptcha\Response($result);
    }

    private function httpPost($url, $data)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
