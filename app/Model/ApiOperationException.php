<?php
declare(strict_types=1);

namespace App\Model;

use RuntimeException;

class ApiOperationException extends RuntimeException
{

    /** @var string|null */
    protected $content;


    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * @param mixed $content
     * @return ApiOperationException
     */
    public function setContent($content): ApiOperationException
    {
        $this->content = $content;
        return $this;
    }
}
