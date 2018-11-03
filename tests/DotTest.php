<?php

namespace Jbizzay\Tests;

use Jbizzay\Dot;

class DotTest extends \PHPUnit\Framework\TestCase
{

    protected function setUp()
    {
        parent::setUp();
        $this->dot = new Dot([
            'a' => ['b', 'c', 'd'],
            'b' => [
                'a' => '1',
                'b' => '2',
                'c' => '3'
            ],
            'c' => [
                'd' => [
                    'e' => 'f'
                ]
            ],
            'd' => [
                'a' => 4,
                'b' => 5.99
            ]
        ]);
    }

    public function testConstruct()
    {
        $dot = new Dot;
        $this->assertEquals([], $dot->get());
        $dot = new Dot(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $dot->get());
    }

    public function testDefineAlreadySet()
    {
        $this->assertEquals(['b', 'c', 'd'], $this->dot->define('a', 'bar'));
        $this->assertEquals('1', $this->dot->define('b.a', 'bar'));
        $this->assertEquals('f', $this->dot->define('c.d.e', 'bar'));
    }

    public function testDefineDefaultsToArray()
    {
        $this->assertSame([], $this->dot->define('foo'));
        $this->assertSame([], $this->dot->get('foo'));
        $this->assertSame([], $this->dot->define('foo.bar'));
        $this->assertSame([], $this->dot->get('foo.bar'));
    }

    public function testDefineValue()
    {
        $this->assertEquals('foo', $this->dot->define('newkey', 'foo'));
        $this->assertEquals('foo', $this->dot->get('newkey'));
        $this->assertEquals('bar', $this->dot->define('new.key.a', 'bar'));
        $this->assertEquals('bar', $this->dot->get('new.key.a'));
    }

    public function testDefineLamda()
    {
        $multiply = function () {
            return 8 * 8;
        };

        $this->assertEquals(64, $this->dot->define('e', $multiply));
        $this->assertEquals(64, $this->dot->get('e'));
        $this->assertEquals(64, $this->dot->define('c.d.f', $multiply));
        $this->assertEquals(64, $this->dot->get('c.d.f'));
    }

    public function testGet()
    {
        $this->dot->set('key', 'val');
        $this->assertEquals('val', $this->dot->get()['key']);
        $this->assertSame(4, $this->dot->get('d.a'));
        $this->assertSame(5.99, $this->dot->get('d.b'));
        $this->assertEquals('f', $this->dot->get('c')['d']['e']);
    }

    public function testGetNotExistsReturnsNull()
    {
        $this->assertNull($this->dot->get('z.z.z.z.z'));
    }

    public function testHas()
    {
        $this->assertTrue($this->dot->has('a'));
        $this->assertTrue($this->dot->has('c.d.e'));
        $this->assertFalse($this->dot->has('z.z.z.z.z.z'));
    }

    public function testHasOnObject()
    {
        $class = new \stdClass;
        $class->one = new \stdClass;
        $class->one->two = 'three';
        $dot = new Dot($class);
        $this->assertFalse($dot->has('one.three'));
        $this->assertTrue($dot->has('one.two'));
    }

    public function testMergeArrayReplacesKey()
    {
        $this->assertEquals(['e' => 321, 'f' => 123], $this->dot->merge('c.d.e', ['e' => 321, 'f' => 123])->get('c.d.e'));
    }

    public function testMergeValueOverArray()
    {
        $this->assertEquals(123, $this->dot->merge(['c' => ['d' => 123]])->get('c.d'));
    }

    public function testMergeArrayWithArrayShouldntHaveDuplicates()
    {
        $this->assertEquals(['b', 'c', 'd', 'e'], $this->dot->merge('a', ['b', 'e'])->get('a'));
    }

    public function testMergeCallable()
    {
        $this->dot->merge(function () {
            return [
                'c' => [
                    'd' => [
                        'e' => 'z',
                        'f' => 'g'
                    ]
                ]
            ];
        });
        $this->assertEquals(['d' => ['e' => 'z', 'f' => 'g']], $this->dot->get('c'));
    }

    public function testMergeArrayWithArrayMixedTypes()
    {
        $this->dot->set('y.x.z', [
            'a',
            'z' => 123,
            'c',
            'f' => [
                'foo'
            ],
            'e' => [
                123 => 'test',
                'hits' => 100
            ]
        ]);
        $this->dot->merge([
            'y' => [
                'x' => [
                    'z' => [
                        'b',
                        'z' => 'foo',
                        'f' => [
                            123 => 999
                        ],
                        'e' => function ($val) {
                            $val['hits']++;
                            return $val;
                        }
                    ]
                ]
            ]
        ]);
        $this->assertEquals([
            'a',
            'z' => 'foo',
            'c',
            'f' => [
                'foo',
                999
            ],
            'e' => [
                123 => 'test',
                'hits' => 101
            ],
            'b'
        ], $this->dot->get('y.x.z'));
        $this->assertEquals(4, $this->dot->get('d.a'));
    }

    public function testSetDefaultsToArray()
    {
        $this->assertSame([], $this->dot->set('x.y.z')->get('x')['y']['z']);
    }

    public function testSetValue()
    {
        $this->assertEquals('123', $this->dot->set('a', '123')->get('a'));
        $this->assertSame(123, $this->dot->set('d.e.f.g', 123)->get('d.e.f.g'));
    }

    public function testSetCallable()
    {
        $this->dot->set('d.a', function ($value) {
            return ++$value;
        })->set('d.a', function ($value) {
            return ++$value;
        });
        $this->assertSame(6, $this->dot->get('d.a'));
    }

    public function testUnset()
    {
        $this->assertNull($this->dot->unset('a')->get('a'));
        $this->assertNull($this->dot->unset('c.d.e')->get('c.d.e'));
    }

    public function testUnsetNotExists()
    {
        $this->assertNull($this->dot->unset('z.z.z.z.z')->get('z.z.z.z.z'));
    }

    public function testGetMixedObjectArrayUsage()
    {
        $obj = new \stdClass;
        $obj->results = [
            'baz' => new \stdClass
        ];
        $obj->results['baz']->somevars = [
            'foo' => new \stdClass
        ];
        $obj->results['baz']->somevars['foo']->test = 123;
        $dot = new Dot($obj);
        $this->assertEquals(123, $dot->get('results.baz.somevars.foo.test'));
        $this->assertTrue(is_object($dot->get('results.baz.somevars.foo')));
    }

    public function testGetNumericIndexes()
    {
        $data = [
            'vars' => [
                'one', 'two'
            ]
        ];
        $dot = new Dot($data);
        $this->assertEquals('one', $dot->get('vars.0'));
    }

}
