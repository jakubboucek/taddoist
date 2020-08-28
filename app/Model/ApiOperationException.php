<?php

declare(strict_types=1);

namespace App\Model;

use RuntimeException;

class ApiOperationException extends RuntimeException
{
    /** @var string|null */
    protected $content;

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content): ApiOperationException
    {
        $this->content = $content;
        return $this;
    }
}
