<?php

namespace Pterodactyl\Repositories\Daemon;

use Psr\Http\Message\ResponseInterface;

class PluginsRepository extends BaseRepository
{
    /**
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/plugins/download', [
            'json' => $data,
        ]);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     */
    public function delete(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/plugins/delete', [
            'json' => $data,
        ]);
    }
}
