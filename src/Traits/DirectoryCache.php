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

            $isValid = file_exists($this->getCacheDirectory()) && !$this->hasCacheExpired();

            $this->logMessage('Cache Valid: ' . var_export($isValid, TRUE));

            return $isValid;
        }

        public function generateCacheKey($filePath) {
            return sha1_file($filePath);
        }

        public function hasCacheExpired() {
            $configFiles = $this->getConfigFile();
            if (!is_array($configFiles)) {
                $configFiles = [$configFiles];
            }

            $cachedHash = $this->loadCachedHash();
            if (empty($cachedHash)) {
                return TRUE;
            }

            $hasExpired = FALSE;

            foreach($configFiles as $configFile) {
                if ($cachedHash[$configFile] !== $this->generateCacheKey($this->getBuildPath() . $configFile)) {
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

            $this->logMessage('Removing Cache Folder: ' . $this->getCacheDirectory());

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
            $this->logMessage('Saving Cache Folder: ' . $this->getCacheDirectory());
            $copyResult = $this->getBuilder()->executeCommand(
                'cp %s %s',
                $this->getDirectory(),
                $this->getCacheDirectory()
            );

            if ($copyResult) {
                $hashArray = $this->getConfigFile();
                if (!is_array($hashArray)) {
                    $hashArray = [$hashArray];
                }

                $hashArray = array_flip($hashArray);
                foreach($hashArray as $fileName => &$fileHash) {
                    $fileHash = sha1_file($this->getDirectory() . DIRECTORY_SEPARATOR . $fileName);
                }

                file_put_contents($this->getHashPath(), '<?php return ' . var_export($hashArray) . ';');
            }

            return $copyResult;
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

        public function loadCachedHash() {
            if (!file_exists($this->getHashPath())) {
                return [];
            }

            return include $this->getHashPath();
        }

        public function getHashPath() {
            return $this->getCacheDirectory() . DIRECTORY_SEPARATOR . 'tf-hash.php';
        }
    }