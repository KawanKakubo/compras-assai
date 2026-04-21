<?php

namespace App\Services\ComprasGov;

class UasgService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    public function search(array $query = []): array
    {
        return $this->client->get('/modulo-uasg/1_consultarUasg', $query);
    }
}