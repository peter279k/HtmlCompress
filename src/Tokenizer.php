<?php declare(strict_types=1);

namespace WyriHaximus\HtmlCompress;

use WyriHaximus\HtmlCompress\Compressor\CompressorInterface;

final class Tokenizer
{
    /**
     * @var array
     */
    private $compressors;

    /**
     * @var Compressor\CompressorInterface
     */
    private $defaultCompressor;

    public function __construct(array $compressors, CompressorInterface $defaultCompressor)
    {
        $this->compressors = $compressors;
        $this->defaultCompressor = $defaultCompressor;
    }

    /**
     * @param  string              $html
     * @param  array               $compressors
     * @param  CompressorInterface $defaultCompressor
     * @return array|Token[]
     */
    public static function tokenize($html, array $compressors, CompressorInterface $defaultCompressor): array
    {
        return (new self($compressors, $defaultCompressor))->parse($html)->getTokens();
    }

    /**
     * @param  string $html
     * @return Tokens
     */
    public function parse($html): Tokens
    {
        $tokens = new Tokens(
            [
                new Token('', $html, '', $this->defaultCompressor),
            ]
        );

        do {
            $compressor = \array_shift($this->compressors);
            $tokens = $this->split($tokens, $compressor);
        } while (\count($this->compressors) > 0);

        return $tokens;
    }

    private function split(Tokens $tokens, array $compressor): Tokens
    {
        foreach ($compressor['patterns'] as $pattern) {
            $tokens = $this->walkTokens($tokens, $pattern, $compressor['compressor']);
        }

        return $tokens;
    }

    private function walkTokens(Tokens $tokens, string $pattern, CompressorInterface $compressor): Tokens
    {
        foreach ($tokens->getTokens() as $index => $token) {
            if ($token->getCompressor() === $this->defaultCompressor) {
                $html = \preg_split($pattern, $token->getCombinedHtml());
                \preg_match_all($pattern, $token->getCombinedHtml(), $bits);

                if (\count($bits[0]) > 0) {
                    $newTokens = $this->walkBits($bits, $html, $compressor);
                    if ($newTokens->count() > 0) {
                        $tokens->replace($index, $newTokens);

                        return $this->walkTokens($tokens, $pattern, $compressor);
                    }
                }
            }
        }

        return $tokens;
    }

    /**
     * @param  array               $bits
     * @param  array               $html
     * @param  CompressorInterface $compressor
     * @return Tokens
     */
    private function walkBits(array $bits, array $html, CompressorInterface $compressor): Tokens
    {
        $newTokens = [];
        $prepend = '';
        for ($i = 0, $iMax = \count($bits[0]); $i < $iMax; $i++) {
            if (
                $bits[1][$i] === ''
                &&
                $bits[2][$i] === ''
                &&
                $bits[3][$i] === ''
            ) {
                continue;
            }

            $newTokens[] = new Token($prepend, $html[$i], $bits[1][$i], $this->defaultCompressor);
            $newTokens[] = new Token('', $bits[2][$i], '', $compressor);
            $prepend = $bits[3][$i];
        }

        if ($prepend !== '' || $html[$i] !== '') {
            $newTokens[] = new Token($prepend, $html[$i], '', $this->defaultCompressor);
        }

        return new Tokens($newTokens);
    }
}
