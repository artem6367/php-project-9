<?php

namespace app;

use GuzzleHttp\Client;

class Url
{
    public int $id;
    public string $name;
    public string $created_at;
    public Check|bool $last_check;

    public function check(): Check|false
    {
        try {
            $client = new Client();
            $response = $client->get($this->name);
            $check = new Check();
            $check->url_id = $this->id;
            $check->status_code = $response->getStatusCode();
            return $check;
        } catch (\Exception $e) {
            return false;
        }
    }
}
