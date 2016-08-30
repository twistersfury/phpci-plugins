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

        /** @var \PHPCI\Builder|\PHPUnit_Framework_MockObject_MockObject */
        private $mockBuilder = NULL;


        /** @var vfsStreamDirectory */
        private $_vfsRoot     = NULL;

        public function setUp() {
            $this->mockBuilder = $this->getMockBuilder('\PHPCI\Builder')
                ->disableOriginalConstructor()
                ->setMethods(['executeCommand'])
                ->getMock();

            $this->_vfsRoot = vfsStream::setup('virtualRoot', NULL, ['somePath' => ['someFolder' => [], 'someFile' => ''], 'someFile' => '']);

            $this->_testSubject = $this->getMockBuilder('\TwistersFury\PhpCi\Traits\DirectoryCache')
                ->setMethods(['getDirectory', 'getCacheRoot', 'getConfigFile', 'getBuildPath', 'getBuilder', 'logMessage'])
                ->getMockForTrait();

            $this->_testSubject->method('getCacheRoot')->willReturn($this->_vfsRoot->url() . '/cache');
            $this->_testSubject->method('getDirectory')->willReturn($this->_vfsRoot->getChild('somePath')->url());
            $this->_testSubject->method('getConfigFile')->willReturn('someFile');
            $this->_testSubject->method('getBuildPath')->willReturn($this->_vfsRoot->url() . '/');
            $this->_testSubject->method('getBuilder')->willReturn($this->mockBuilder);
            $this->_testSubject->method('logMessage')->willReturnSelf();
        }

        public function testGetCacheDirectory() {
            $this->assertEquals($this->_vfsRoot->url() . '/cache/somePath', $this->_testSubject->getCacheDirectory());
        }

        /**
         * @covers TwistersFury\PhpCi\Traits\DirectoryCache::isCacheValid
         * @covers TwistersFury\PhpCi\Traits\DirectoryCache::getDirectory
         */
        public function testIsCacheValid() {
            $this->assertFalse($this->_testSubject->isCacheValid(), 'Failed Checking Cache Does Not Exit');

            $vfsCache      = vfsStream::newDirectory('cache');
            $vfsDirectory  = vfsStream::newDirectory('somePath');

            $vfsCache->addChild($vfsDirectory);
            $this->_vfsRoot->addChild($vfsCache);

            $this->assertFalse($this->_testSubject->isCacheValid(), 'Failed Checking Caches Exists But Expired');
        }

        public function testHasCacheExpired() {
            $this->assertTrue($this->_testSubject->hasCacheExpired());

            $hashArray = [
                'someFile' => sha1_file($this->_vfsRoot->getChild('somePath/someFile')->url())
            ];

            $vfsCache        = vfsStream::newDirectory('cache');
            $vfsDirectory    = vfsStream::newDirectory('somePath');
            $vfsFile         = vfsStream::newFile('tf-hash.php');

            $vfsFile->setContent('<?php return ' . var_export($hashArray, TRUE) . ';');

            $vfsDirectory->addChild($vfsFile);
            $vfsCache->addChild($vfsDirectory);
            $this->_vfsRoot->addChild($vfsCache);

            $this->assertFalse($this->_testSubject->hasCacheExpired());

            $hashArray = [
                'someFile' => 'something'
            ];

            $vfsFile->setContent('<?php return ' . var_export($hashArray, TRUE) . ';');
            $this->assertTrue($this->_testSubject->hasCacheExpired());
        }

        public function testRemoveCache() {
            $this->assertEquals($this->_testSubject, $this->_testSubject->removeCache());

            $vfsCache        = vfsStream::newDirectory('cache');
            $vfsDirectory    = vfsStream::newDirectory('somePath');
            $vfsSubDirectory = vfsStream::newDirectory('subPath');
            $vfsFile         = vfsStream::newFile('someFile', 'someContent');

            $vfsDirectory->addChild($vfsFile);
            $vfsDirectory->addChild($vfsSubDirectory);
            $vfsCache->addChild($vfsDirectory);
            $this->_vfsRoot->addChild($vfsCache);

            $this->assertEquals($this->_testSubject, $this->_testSubject->removeCache());

            $this->assertNull($vfsCache->getChild('somePath'));
        }

        public function testSaveCache() {
            $this->assertFalse(file_exists($this->_testSubject->getCacheDirectory()));

            $this->assertEquals($this->_testSubject, $this->_testSubject->saveCache());

            $this->assertEquals(0755, $this->_vfsRoot->getChild('cache')->getPermissions());
            $this->assertEquals(0755, $this->_vfsRoot->getChild('cache/somePath')->getPermissions());
            $this->assertEquals(0755, $this->_vfsRoot->getChild('cache/somePath/someFolder')->getPermissions());

            $this->assertTrue(file_exists($this->_testSubject->getCacheDirectory() . '/someFile'));
        }

        public function testCopyCache() {
            $vfsCache        = vfsStream::newDirectory('cache');
            $vfsDirectory    = vfsStream::newDirectory('somePath');
            $vfsSubDirectory = vfsStream::newDirectory('subPath');
            $vfsFile         = vfsStream::newFile('someFile', 'someContent');

            $vfsDirectory->addChild($vfsFile);
            $vfsDirectory->addChild($vfsSubDirectory);
            $vfsCache->addChild($vfsDirectory);
            $this->_vfsRoot->addChild($vfsCache);

            $this->mockBuilder->expects($this->once())
                ->method('executeCommand')
                ->willReturn(TRUE);

            $this->assertTrue($this->_testSubject->copyCache());
            $this->assertFileExists($this->_testSubject->getDirectory());
        }

        /**
         * Test Not Really Needed. Just For Code Coverage.
         */
        public function testGetCacheRoot() {
            /** @var \TwistersFury\PhpCi\Traits\DirectoryCache $testSubject */
            $testSubject = $this->getMockBuilder('\TwistersFury\PhpCi\Traits\DirectoryCache')
                ->getMockForTrait();

            $this->assertEquals('/tmp/twistersfury-phpci-cache', $testSubject->getCacheRoot());
        }
    }
