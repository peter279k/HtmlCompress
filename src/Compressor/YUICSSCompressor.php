<?php declare(strict_types=1);

namespace WyriHaximus\HtmlCompress\Compressor;

use YUI\Compressor as YUICompressor;

final class YUICSSCompressor extends Compressor
{
    /** @var YUICompressor */
    private $yui;

    public function __construct()
    {
        $this->yui = new YUICompressor();
        $this->yui->setType(YUICompressor::TYPE_CSS);
    }

    protected function execute(string $string): string
    {
        try {
            return $this->yui->compress($string);
        } catch (\Exception $exception) {
            return $string;
        }
    }
}
