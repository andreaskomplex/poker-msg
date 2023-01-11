<?php
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use PokerMsg\MsgServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$ws = new WsServer(new MsgServer);
$server = IoServer::factory(new HttpServer($ws), 8443, '127.0.0.1');
$server->run();