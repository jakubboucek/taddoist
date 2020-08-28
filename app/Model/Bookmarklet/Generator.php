<?php

declare(strict_types=1);

namespace App\Model\Bookmarklet;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Generator
{
    private const IDENTIFICATOR_ENDPOINT = '$__endpoint__';
    private const IDENTIFICATOR_PROJECT = '$__projectId__';
    private const IDENTIFICATOR_NEWWIN = '$__newWindow__';

    /**
     * @param string $endpoint
     * @param string|null $projectId
     * @param bool $newWindow
     * @return string
     * @throws JsonException
     */
    public static function generate(string $endpoint, ?string $projectId = null, bool $newWindow = true): string
    {
        $bookmarklet = 'javascript:' . static::getTemplate();
        $bookmarklet = str_replace(
            [
                static::IDENTIFICATOR_ENDPOINT,
                static::IDENTIFICATOR_PROJECT,
                static::IDENTIFICATOR_NEWWIN
            ],
            [
                Json::encode($endpoint),
                Json::encode($projectId),
                Json::encode($newWindow)
            ],
            $bookmarklet
        );

        return $bookmarklet;
    }

    private static function getTemplate(): string
    {
        $fileName = __DIR__ . '/template.js';
        return FileSystem::read($fileName);
    }
}
