<?php
namespace Hexagon\test\system\uri;

require implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'TestBootstrap.php']);

use \Hexagon\TestBootstrap;
use \PHPUnit_Framework_TestCase;
use \Hexagon\system\uri\Router;
use \Exception;
use \ReflectionClass;

TestBootstrap::registerTestNamespace(__NAMESPACE__, 'Hexagon\config\BaseConfig');

class RouterTest extends PHPUnit_Framework_TestCase {
    
    /**
     * @dataProvider paramsProvider
     */
    public function testRouter($uri, $result) {
        $router = Router::getInstance();
        try {
            $this->assertEquals($result, $router->resolveURI($uri));
        } catch (Exception $e) {
            $this->assertEquals($result, (new ReflectionClass($e))->getShortName());
        }
    }
    
    public function paramsProvider() {
        return [
            ['/welcome/new_world', '/welcome/new_world'], 
            ['/test/index', '/test/index'],
            ['aaaa/test/index', '/aaaa/test/index'],
            ['index', '/welcome/index'],
            ['BBB/test/fasd/my_fuc', '/BBB/test/fasd/my_fuc'],
            ['hello,world', 'InvalidURI']
        ];
    }
}
