<?php
namespace E4u\Form\Recaptcha;

class Response
{
    private $data;

    public function __construct(string $response)
    {
        $this->data = \GuzzleHttp\json_decode($response, true);
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
