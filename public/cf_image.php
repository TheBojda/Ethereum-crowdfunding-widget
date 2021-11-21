<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.inc.php';

use Web3\Web3;
use Web3\Contract;
use Endroid\QrCode\QrCode;
use GuzzleHttp\Promise\Promise;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\ImageManagerStatic as Image;

$promise = new Promise(function () use (&$promise) {
    $web3 = new Web3(PROVIDER_URL);
    $web3->eth->getBalance(ETH_ADDRESS, function ($err, $balance) use ($promise) {
        if ($err !== null) {
            $promise->reject($err->getMessage());
        } else {
            $promise->resolve($balance);
        }
    });
});
$balance = floatval($promise->wait()->toString());

/*
$promise = new Promise(function () use (&$promise) {
    $contract = new Contract(PROVIDER_URL, file_get_contents('../erc20_abi.json'));
    $contract->at(CONTRACT_ADDRESS)->call('balanceOf', ETH_ADDRESS, function ($err, $balance) use ($promise) {
        if ($err !== null) {
            $promise->reject($err->getMessage());
        } else {
            $promise->resolve($balance);
        }
    });
});
$balance = floatval($promise->wait()['balance']->toString());
*/

$percent = $balance / TARGET_VALUE;
if ($percent > 1)
    $percent = 1;

$qrCode = QrCode::create(ETH_ADDRESS)
    ->setSize(180)
    ->setMargin(0);
$result = (new PngWriter())->write($qrCode);

$image = Image::canvas(200, 270, '#aaaaaa');
$image->insert($result->getDataUri(), "top-left", 10, 10);
$image->text(
    'Send me some ETH',
    10,
    210,
    function ($font) {
        $font->file('../OpenSans-Bold.ttf');
        $font->size(12);
        $font->color('#000000');
    }
);
$image->text(
    number_format($balance / 10 ** 18, 2) . " ETH" . ' / ' . number_format(TARGET_VALUE / 10 ** 18, 2) . ' ETH',
    10,
    230,
    function ($font) {
        $font->file('../OpenSans-Regular.ttf');
        $font->size(12);
        $font->color('#000000');
    }
);
$image->rectangle(10, 240, 190, 250, function ($draw) {
    $draw->background('#ffffff');
    $draw->border(1, '#000000');
});
$image->rectangle(10, 240, 10 + round($percent * 180), 250, function ($draw) {
    $draw->background('#037362');
    $draw->border(1, '#000000');
});

header('Content-Type: image/png');
echo $image->encode('png');
