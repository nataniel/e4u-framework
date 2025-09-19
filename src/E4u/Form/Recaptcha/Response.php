<?php
namespace E4u\Form\Recaptcha;

use GuzzleHttp\Utils;

class Response
{
    private array $data;

    public function __construct(string $response)
    {
        $this->data = Utils::jsonDecode($response, true);
    }

    public function isSuccess(): bool
    {
        return $this->data['success'];
    }

    public function getChallengeTimestamp(): \DateTime
    {
        return new \DateTime($this->data['challenge_ts']);
    }

    public function getHostname(): string
    {
        return $this->data['hostname'];
    }

    public function getScore(): ?float
    {
        return $this->data['score'];
    }

    public function getAction(): ?string
    {
        return $this->data['action'];
    }

    public function getErrors(): ?array
    {
        return $this->data['error-codes'];
    }
}
