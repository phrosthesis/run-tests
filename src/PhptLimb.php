<?php

declare(strict_types=1);

namespace Phrosthesis\RunTests;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\Util\HttpDownloader;

final class PhptLimb implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var string
     */
    private $runner = 'run-tests.php';

    const BASE_URL = 'https://raw.githubusercontent.com/php/php-srcz';

    /**
     * @param string|null $runner
     */
    public function __construct($runner = null)
    {
        if ($runner !== null) {
            $this->runner = $runner;
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onAutoloadDump',
        ];
    }

    /**
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io) {}

    /**
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io) {}

    /**
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        $fs = new Filesystem();

        if ($fs->remove($this->runner)) {
            $io->write("<comment>Phrosthesis</comment>: removed <info>{$this->runner}</info>");
        }
    }

    /**
     * @return void
     */
    public function onAutoloadDump(Event $event)
    {
        $io = $event->getIO();

        if (!$event->isDevMode()) {
            $io->write('<comment>Phrosthesis</comment>: dev mode required', true, IOInterface::VERY_VERBOSE);
            return;
        }

        $http = new HttpDownloader($io, $event->getComposer()->getConfig());

        $refs = [
            'PHP-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
            'PHP-' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'PHP-' . PHP_MAJOR_VERSION,
            'master',
        ];

        foreach ($refs as $ref) {
            $url = "https://raw.githubusercontent.com/php/php-src/{$ref}/{$this->runner}";

            $io->write("<comment>Phrosthesis</comment>: downloading <info>{$url}</info>", false, IOInterface::VERBOSE);

            try {
                $http->copy($url, $this->runner);
            } catch (\Exception $ex) {
                $io->write(" ({$ex->getCode()})", true, IOInterface::VERBOSE);
                continue;
            }

            $io->write('', true, IOInterface::VERBOSE);
            $io->write("<comment>Phrosthesis</comment>: downloaded <info>{$this->runner}</info>");

            return;
        }

        $io->writeError("<comment>Phrosthesis</comment>: could not download <error>{$this->runner}</error>");
    }
}
