<?php

declare(strict_types=1);

namespace blt;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Nette\Utils\Finder;
use SplFileInfo;

class Converter
{
    public const LANG_VAR = '_lang';
    public const COUNTRY_LANG_VAR = '_country_lang';

    private $fetchingVariable;

    public function __construct(string $fetchingVariable)
    {
        $this->fetchingVariable = $fetchingVariable;
    }

    /**
     * Runs process of converting the lexicon strings
     *
     * @param string $lexiconsRoot
     */
    public function __invoke(string $lexiconsRoot): void
    {
        foreach ($this->findLexiconFiles($lexiconsRoot) as $lexiconFile)
        {
            $this->processLexiconFile($lexiconFile);
        }
    }

    /**
     * @param string $root
     *
     * @return SplFileInfo[]
     */
    protected function findLexiconFiles(string $root): array
    {
        $collection = Finder::findFiles('*.inc.php')->from($root);

        $sources = [];
        /** @var SplFileInfo $fileInfo */
        foreach ($collection as $fileInfo) {
            if (false === strpos($fileInfo->getFilename(), '-lt')) {
                $sources[] = $fileInfo;
            }
        }

        return $sources;
    }

    /**
     * @param SplFileInfo $file
     */
    protected function processLexiconFile(SplFileInfo $file): void
    {
        $this->log('Processing the file %s', $file->getFilename());

        $entries = $this->fetchLexiconEntries($file->getRealPath());

        /** @var string $entry */
        foreach ($entries as $key => &$entry)
        {
            $entry = $this->lexiconEntryBuilder($key, $this->sanitizeLine($this->convertLine($entry)));
        }

        $topic = ucfirst(str_replace('.inc.php', '', $file->getFilename()));
        $target = $this->fetchingVariable === self::LANG_VAR
            ? sprintf('%s-lt/%s', $file->getPath(), $file->getFilename())
            : sprintf('%s/%s', $file->getPath(), str_replace('be', 'be-lt', $file->getFilename()));

        $this->writeFile($target, $this->buildPhpFileContent($topic, $entries));
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function fetchLexiconEntries(string $path): array
    {
        $lexicons = ${self::LANG_VAR} = ${self::COUNTRY_LANG_VAR} = [];

        if (file_exists($path)) {
            include $path;
            $lexicons = ${$this->fetchingVariable};
        }

        return $lexicons;
    }

    /**
     * @param string $line
     *
     * @return string
     */
    protected function convertLine(string $line): string
    {
        $command = escapeshellcmd(sprintf(__DIR__ . '/../scripts/blt.py "%s"', $line));

        return trim(shell_exec($command));
    }

    protected function sanitizeLine(string $line): string
    {
        $replacements = ['?',
            '<', '>',
            '{', '}',
            '[', ']',
            '(', ')'
        ];

        return str_replace(array_map(fn($v) => '\\' . $v, $replacements), $replacements, $line);
    }

    /**
     * @param string $topic
     * @param array  $entries
     *
     * @return string
     */
    protected function buildPhpFileContent(string $topic, array $entries): string
    {
        $comment = [
            sprintf('Belarusian Latin lexicon – topic %s', $topic), '',
            "@language\tbe-lt\tBielaruskaja lacinka",
            "@package\t\tmodx",
            "@subpackage\tlexicon"
        ];

        $phpFile = new PhpFile();
        $phpFile->addComment(implode(PHP_EOL, $comment));

        return (new PsrPrinter)->printFile($phpFile) . implode(PHP_EOL, $entries) . PHP_EOL;
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
    private function lexiconEntryBuilder(string $key, string $value): string
    {
        return sprintf('$_lang[\'%s\'] = \'%s\';', $key, $value);
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
}
