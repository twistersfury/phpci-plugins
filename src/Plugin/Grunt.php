<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/25/16
     * Time: 8:49 PM
     */

    namespace TwistersFury\PhpCi\Plugin;

    use PHPCI\Plugin\Grunt as pcGrunt;
    use Psr\Log\LogLevel;
    use TwistersFury\PhpCi\Traits\DirectoryCache;

    class Grunt extends pcGrunt {
        use DirectoryCache;

        public function getDirectory() {
            return $this->getBuildPath() . 'node_modules';
        }

        public function getConfigFile() {
            return ['package.json', 'Gruntfile.js'];
        }

        public function execute() {
            if (!$this->isCacheValid()) {
                $this->removeCache();
            }

            if (($parentResult = parent::execute() === TRUE)) {
                $this->saveCache();
            }

            return $parentResult;
        }

        public function logMessage($message, $level = LogLevel::INFO, $context = []) {
            $this->phpci->log($message, $level, $context);
        }
    }