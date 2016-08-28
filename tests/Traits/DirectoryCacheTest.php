<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/25/16
     * Time: 11:44 PM
     */
    namespace TwistersFury\PhpCi\tests\Traits;

    use org\bovigo\vfs\vfsStream;
    use org\bovigo\vfs\vfsStreamDirectory;

    class DirectoryCacheTest extends \PHPUnit_Framework_TestCase {
        /** @var \TwistersFury\PhpCi\Traits\DirectoryCache|\PHPUnit_Framework_MockObject_MockObject */
        private $_testSubject = NULL;


        /** @var vfsStreamDirectory */
        private $_vfsRoot     = NULL;

        public function setUp() {
            $this->_vfsRoot = vfsStream::setup('virtualRoot', NULL, ['somePath' => ['someFile' => '']]);

            $this->_testSubject = $this->getMockBuilder('\TwistersFury\PhpCi\Traits\DirectoryCache')
                ->setMethods(['getDirectory', 'getCacheRoot'])
                ->getMockForTrait();

            $this->_testSubject->method('getCacheRoot')->willReturn($this->_vfsRoot->url() . '/cache');
            $this->_testSubject->method('getDirectory')->willReturn($this->_vfsRoot->getChild('somePath')->url());
        }

        public function testGetCacheDirectory() {
            $this->assertEquals($this->_vfsRoot->url() . '/cache/somePath', $this->_testSubject->getCacheDirectory());
        }

        /**
         * @covers TwistersFury\PhpCi\Traits\DirectoryCache::isCacheValid
         * @covers TwistersFury\PhpCi\Traits\DirectoryCache::hasCacheExpired
         * @covers TwistersFury\PhpCi\Traits\DirectoryCache::getDirectory
         */
        public function testIsCacheValid() {
            $this->assertFalse($this->_testSubject->isCacheValid(), 'Failed Checking Cache Does Not Exit');

            $vfsCache     = vfsStream::newDirectory('cache');
            $vfsDirectory = vfsStream::newDirectory('somePath');

            $vfsDirectory->lastModified(time() + 100);
            $this->_vfsRoot->getChild('somePath')->lastModified(time() - 100);

            $vfsCache->addChild($vfsDirectory);
            $this->_vfsRoot->addChild($vfsCache);

            $this->assertTrue($this->_testSubject->isCacheValid(), 'Failed Checking Cache Does Exist');

            $vfsDirectory->lastModified(time() - 100);
            $this->_vfsRoot->getChild('somePath')->lastModified(time() + 100);

            $this->assertFalse($this->_testSubject->isCacheValid(), 'Failed Checking Caches Exists But Expired');

        }

        public function testRemoveCache() {
            $vfsCache     = vfsStream::newDirectory('cache');
            $vfsDirectory = vfsStream::newDirectory('somePath');
            $vfsFile      = vfsStream::newFile('someFile', 'someContent');

            $vfsDirectory->addChild($vfsFile);
            $vfsCache->addChild($vfsDirectory);
            $this->_vfsRoot->addChild($vfsCache);

            $this->_testSubject->removeCache();

            $this->assertNull($vfsCache->getChild('somePath'));
        }

        public function testSaveCache() {
            $this->assertFalse(file_exists($this->_testSubject->getCacheDirectory()));

            $this->_testSubject->saveCache();

            $this->assertTrue(file_exists($this->_testSubject->getCacheDirectory() . '/someFile'));
        }
    }
