<?php
    /**
     * Created by PhpStorm.
     * User: fenikkusu
     * Date: 8/25/16
     * Time: 10:05 PM
     */
    namespace TwistersFury\PhpCi\tests\Plugin;

    use org\bovigo\vfs\vfsStream;
    use TwistersFury\PhpCi\Plugin\Grunt;

    class GruntTest extends \PHPUnit_Framework_TestCase {
        /** @dataProvider _dpTestExecute */
        public function testExecute($returnValue) {
            $configFile = '/some/path';

            $vfsRoot = vfsStream::setup('virtualRoot');

            include_once dirname(PHPUNIT_COMPOSER_INSTALL) . '/block8/phpci/vars.php';

            $mockBuilder = $this->getMockBuilder('\PHPCI\Builder')
                ->disableOriginalConstructor()
                ->setMethods(['executeCommand', 'findBinary', 'log'])
                ->getMock();

            //NOTE: Don't Really Care What It Is Called With. This Just Confirms The Parent Gets Called.
            $mockBuilder->expects($this->exactly($returnValue ? 2 : 1))
                ->method('executeCommand')
                ->willReturn($returnValue);

            $mockBuilder->expects($this->once())
                ->method('findBinary')
                ->willReturn('/some/path');

            $mockModel = $this->getMockBuilder('\PHPCI\Model\Build')
                ->disableOriginalConstructor()
                ->getMock();

            /** @var Grunt|\PHPUnit_Framework_MockObject_MockObject $testGrunt */
            $testGrunt = $this->getMockBuilder('\TwistersFury\PhpCi\Plugin\Grunt')
                ->setConstructorArgs([$mockBuilder, $mockModel, []])
                ->setMethods(['saveCache', 'removeCache', 'getBuildPath'])
                ->getMock();

            $testGrunt->method('getBuildPath')->willReturn($vfsRoot->url() . '/');

            $testGrunt->expects($this->once())
                ->method('removeCache')
                ->willReturnSelf();

            if (!$returnValue) {
                $testGrunt->expects($this->never())
                    ->method('saveCache');
            } else {
                $testGrunt->expects($this->once())
                    ->method('saveCache')
                    ->willReturnSelf();
            }

            $this->assertEquals($returnValue, $testGrunt->execute());
        }

        public function _dpTestExecute() {
            return [
                [FALSE],
                [TRUE]
            ];
        }
    }
