<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/25/16
     * Time: 7:53 PM
     */

    namespace TwistersFury\PhpCi\Traits;

    use Psr\Log\LogLevel;

    /**
     * Class DirectoryCache
     *
     * @package TwistersFury\PhpCi\Traits
     * @property \PHPCI\Builder phpci
     * @property string directory
     */
    trait DirectoryCache {
        abstract public function getDirectory();
        abstract public function getConfigFile();

        /**
         * @param        $message
         * @param string $level
         * @param array  $context
         *
         * @return $this
         */
        abstract public function logMessage($message, $level = LogLevel::INFO, $context = []);

        /**
         * @return \PHPCI\Builder
         */
        abstract public function getBuilder();

        public function getCacheRoot() {
            return '/tmp/twistersfury-phpci-cache';
        }

        public function getCacheDirectory() {
            return $this->getCacheRoot() . '/' . basename($this->getDirectory());
        }

        public function isCacheValid() {
            $this->logMessage('Directory Cache: ' . $this->getCacheDirectory());
            $this->logMessage('Directory: ' . $this->getDirectory());

            if (!file_exists($this->getCacheDirectory()) || $this->hasCacheExpired()) {
                return FALSE;
            }

            return TRUE;
        }

        public function hasCacheExpired() {
            $configFiles = $this->getConfigFile();
            if (!is_array($configFiles)) {
                $configFiles = [$configFiles];
            }

            $cacheTime  = filemtime($this->getCacheDirectory());
            $hasExpired = FALSE;

            foreach($configFiles as $configFile) {
                if ($cacheTime < filemtime($this->getBuildPath() . $configFile)) {
                    $hasExpired = TRUE;
                    break;
                }
            }

            return $hasExpired;
        }

        public function removeCache() {
            if (!file_exists($this->getCacheDirectory())) {
                return $this;
            }

            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getCacheDirectory(), \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $filePath) {
                if ($filePath->isDir()) {
                    rmdir($filePath);
                } else {
                    unlink($filePath);
                }
            }

            rmdir($this->getCacheDirectory());

            return $this;
        }

        public function saveCache() {
            if (!file_exists($this->getCacheRoot())) {
                mkdir($this->getCacheRoot(), 0755);
            }

            mkdir($this->getCacheDirectory(), 0755);

            $basePath = dirname($this->getDirectory());

            /** @var \SplFileInfo $filePath */
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDirectory(), \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $filePath) {
                $newPath = $this->getCacheRoot() . str_replace($basePath, '', $filePath);
                $oldPath = $filePath->getPathname();

                if ($filePath->isDir()) {
                    mkdir($newPath, 0755);
                } else {
                    copy($oldPath, $newPath);
                }
            }

            return $this;
        }

        public function copyCache() {
            return $this->logMessage('Using Cache Folder: ' . $this->getCacheDirectory())
                ->getBuilder()
                ->executeCommand(
                    'cp %s %s',
                    rtrim($this->getCacheDirectory(), DIRECTORY_SEPARATOR),
                    $this->getBuildPath()
                );
        }

        public function getBuildPath() {
            return $this->directory;
        }
    }