<?php

use Sqids\Sqids;

function encodeId($id): string
{
    $sqids = new Sqids(minLength: 10);
    return $sqids->encode([$id]);
}

function decodeId($id): int
{
    $sqids = new Sqids(minLength: 10);
    return $sqids->decode($id)[0] ?? 0;
}
