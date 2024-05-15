<?php

function get_redis(): Redis
{
    $redis = new Redis();
    $redis->connect('localhost', 6379);
    $redis->select(3);
    return $redis;
}