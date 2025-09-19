<?php
namespace E4u\Form;

class Recaptcha
{
    const string VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const string FIELD_NAME = 'g-recaptcha-response';

    private ?string $sitekey;
    private string $secret;

    public function __construct(string $secret, ?string $sitekey = null)
    {
        $this->sitekey = $sitekey;
        $this->secret = $secret;
    }

    public function verifyResponse($response): Recaptcha\Response
    {
        $result = $this->httpPost([
            'secret' => $this->secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'],
        ]);

        return new Recaptcha\Response($result);
    }

    private function httpPost(array|object $data): bool|string
    {
        $curl = curl_init(self::VERIFY_URL);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
