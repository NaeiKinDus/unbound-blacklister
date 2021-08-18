<?php

namespace Nullified\Parsers\Interfaces;

use Nullified\Data\Adlist;

interface ParserInterface
{
    /**
     * @param mixed $content Content of an adlist file to be parsed
     */
    public static function parse(Adlist $adlist, $content): void;
}