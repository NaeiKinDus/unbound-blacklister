<?php

namespace Nullified\Data;

use DateTime;

class Adlist
{
    /**
     * @var string Source (path, URI, ...).
     */
    public string       $source;

    /**
     * @var array<string> A list of ad serving domains to be blocked.
     */
    public array        $blockList = [];

    /**
     * @var DateTime|null Date of the last update, if a local database is available.
     */
    public ?DateTime    $lastUpdate;

    /**
     * @var int Number domains the ad list contains.
     */
    public int          $size;
}
