<?php

declare(strict_types=1);

namespace blt;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Nette\Utils\Finder;
use SplFileInfo;

class Converter
{
    /**
     * Runs process of converting the lexicon strings
     *
     * @param string $lexiconsRoot
     */
    public function __invoke(string $lexiconsRoot): void
    {
        foreach ($this->scanFiles($lexiconsRoot) as $file)
        {
            $this->process($file);
        }
    }

    /**
     * @param string $root
     *
     * @return array
     */
    protected function scanFiles(string $root): array
    {
        $collection = Finder::findFiles('*.inc.php')->from($root);

        $sources = [];
        /** @var SplFileInfo $file */
        foreach ($collection as $file) {
            if (false === strpos($file->getFilename(), '-lt')) {
                $sources[] = $file;
            }
        }

        return $sources;
    }

    /**
     * @param SplFileInfo $file
     */
    protected function process(SplFileInfo $file)
    {
        $this->log('Processing file %s', $file->getFilename());

        $entries = $this->fetchLexiconEntries($file->getRealPath());

        foreach ($entries as $key => &$entry)
        {
            $entry = $this->languageEntryBodyBuilder($key, $this->convertLine($entry));
        }

        $newPath = sprintf('%s-lt/%s', $file->getPath(), $file->getFilename());
        $topic = ucfirst(str_replace('.inc.php', '', $file->getFilename()));

        $this->storeEntries($newPath, $topic, $entries);
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function fetchLexiconEntries(string $path): array
    {
        // $_country_lang - для стран

        $lexicons = $_lang = [];

        if (file_exists($path)) {
            include $path;
            $lexicons = $_lang;
        }

        return $lexicons;
    }

    /**
     * @param string $line
     *
     * @return string
     */
    public function convertLine(string $line): string
    {
        $command = escapeshellcmd(sprintf(__DIR__ . '/../scripts/blt.py "%s"', $line));

        return trim(shell_exec($command));
    }

    /**
     * @param       $message
     * @param mixed ...$arguments
     *
     * @return void
     */
    private function log($message, ...$arguments): void
    {
        echo sprintf($message, ...$arguments), PHP_EOL;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    private function languageEntryBodyBuilder(string $key, string $value): string
    {
        return sprintf('$_lang[\'%s\'] = \'%s\'', $key, $value);
    }

    /**
     * @param string $filePath
     * @param string $content
     */
    private function writeFile(string $filePath, string $content): void
    {
        $fileHandler = fopen($filePath, 'wb+');
        fwrite($fileHandler, $content);
        fclose($fileHandler);
    }

    protected function storeEntries(string $filename, string $topic, array $entries): void
    {
        $this->log('Storing new entries for topic %s to file %s', $topic, $filename);

        $comment = [
            sprintf('Belarusian Latin lexicon – topic %s', $topic), '',
            "@language\tbe-lt\tBielaruskaja lacinka",
            "@package\tmodx",
            "@subpackage\tlexicon"
        ];

        $phpFile = new PhpFile();
        $phpFile->addComment(implode(PHP_EOL, $comment));

        $content = (new PsrPrinter)->printFile($phpFile) . implode(PHP_EOL, $entries) . PHP_EOL;

        $this->writeFile($filename, $content);
    }
}
