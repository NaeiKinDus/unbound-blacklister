<?php

namespace Nullified\Parsers;

use Nullified\Data\Adlist;
use Nullified\Parsers\Interfaces\ParserInterface;

class TextParser implements ParserInterface
{
    public const IPV4_PART_REGEX = '(?:0|[1-9][0-9]?|1[0-9]{2}|2[0-4][0-9]|25[0-5])';
    public const IPV4_REGEX = '^(?:' . self::IPV4_PART_REGEX . '\.){3}' . self::IPV4_PART_REGEX . '$';
    public const DOMAIN_NAME_REGEX = '(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]';

    public static function parse(Adlist $adlist, $content): void
    {
        $lines = preg_split('/[\r\n]+/', rtrim($content));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line[0] === '#') {
                continue;
            }
            // Remove comments
            $parts = explode('#', $line);
            $cleaned = $parts[0];

            $parts = preg_split('/\s+/', $cleaned);
            $count = count($parts);
            if ($count === 1) {
                $domain = $parts[0];
            } else if ($count === 2) {
                if (preg_match('/^' . self::DOMAIN_NAME_REGEX . '$/', $parts[0])) {
                    $domain = $parts[0];
                } else if (preg_match('/^' . self::DOMAIN_NAME_REGEX . '$/', $parts[1])) {
                    $domain = $parts[1];
                } else {
                    // add error; format unrecognized
                    // check hardfail
                    continue;
                }
            } else {
                // add error; format unrecognized
                // check hardfail
                continue;
            }

            $adlist->blockList[] = $domain;
        }
        $adlist->size = count($adlist->blockList);
    }
}