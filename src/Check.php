<?php

namespace app;

class Check
{
    public int $id;
    public int $url_id;
    public ?int $status_code;
    public ?string $h1;
    public ?string $title;
    public ?string $description;
    public ?string $created_at;
}
