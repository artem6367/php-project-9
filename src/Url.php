<?php

namespace app;

use DiDom\Document;
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
            $guzzleStream = $response->getBody();
            $document = new Document();
            $document->loadHtml($guzzleStream->getContents());
            $check->h1 = optional($document->first('h1'))->text();
            $check->title = optional($document->first('head')->first('title'))->text();
            $check->description = optional(
                $document->first('head')->first('meta[name=description]')
            )->getAttribute('content');
            return $check;
        } catch (\Exception $e) {
            return false;
        }
    }
}
