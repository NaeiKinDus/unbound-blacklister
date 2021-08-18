<?php

namespace Nullified\Data;

use DateTime;

class Adlist
{
    /**
     * Source (path, URI, ...).
     * @var string
     */
    public string       $source;

    /**
     * A list of ad serving domains to be blocked.
     * @var array<string>
     */
    public array        $blockList = [];

    /**
     * Date of the last update, if a local database is available.
     * @var DateTime|null
     */
    public ?DateTime    $lastUpdate;
}
