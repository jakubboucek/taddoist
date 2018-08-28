<?php
declare(strict_types=1);

namespace App\Model\Bookmarklet;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;

class Generator
{
    private const IDENTIFICATOR_ENDPOINT = '$__endpoint__';
    private const IDENTIFICATOR_PROJECT = '$__projectId__';


    /**
     * @param string $endpoint
     * @param null|string $projectId
     * @return string
     * @throws \Nette\Utils\JsonException
     * @throws \Nette\IOException
     */
    public static function generate(string $endpoint, ?string $projectId = null): string
    {
        $bookmarklet = 'javascript:' . static::getTemplate();
        $bookmarklet = str_replace(
            [
                static::IDENTIFICATOR_ENDPOINT,
                static::IDENTIFICATOR_PROJECT
            ],
            [
                Json::encode($endpoint),
                Json::encode($projectId)
            ],
            $bookmarklet
        );

        return $bookmarklet;

    }


    /**
     * @return string
     * @throws \Nette\IOException
     */
    private static function getTemplate(): string
    {
        $fileName = __DIR__ . '/template.js';
        return FileSystem::read($fileName);
    }
}
