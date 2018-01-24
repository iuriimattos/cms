<?php

namespace Tests\Tags;

use Statamic\API\File;
use Tests\TestCase;
use Statamic\API\Parse;

class ThemeTagsTest extends TestCase
{
    use PartialTests;

    protected $path;

    public function setUp()
    {
        $this->path = '';

        parent::setUp();
    }

    private function tag($tag)
    {
        return Parse::template($tag, []);
    }

    protected function partialTag($src, $params = '')
    {
        return $this->tag("{{ theme:partial src=\"$src\" $params }}");
    }

    public function testOutputsThemedJs()
    {
        $this->assertEquals(
            $this->path.'/js/app.js',
            $this->tag('{{ theme:js }}')
        );
    }

    public function testOutputsNamedJs()
    {
        $this->assertEquals(
            $this->path.'/js/script.js',
            $this->tag('{{ theme:js src="script.js" }}')
        );
    }

    public function testOutputsNamedJsWithAppendedExtension()
    {
        $this->assertEquals(
            $this->path.'/js/script.js',
            $this->tag('{{ theme:js src="script" }}')
        );
    }

    public function testOutputsJsTag()
    {
        $this->assertEquals(
            '<script src="'.$this->path.'/js/script.js"></script>',
            $this->tag('{{ theme:js src="script" tag="true" }}')
        );
    }

    public function testOutputsThemedCss()
    {
        $this->assertEquals(
            $this->path.'/css/app.css',
            $this->tag('{{ theme:css }}')
        );
    }

    public function testOutputsNamedCss()
    {
        $this->assertEquals(
            $this->path.'/css/style.css',
            $this->tag('{{ theme:css src="style.css" }}')
        );
    }

    public function testOutputsNamedCssWithAppendedExtension()
    {
        $this->assertEquals(
            $this->path.'/css/style.css',
            $this->tag('{{ theme:css src="style" }}')
        );
    }

    public function testOutputsCssTag()
    {
        $this->assertEquals(
            '<link rel="stylesheet" href="'.$this->path.'/css/style.css" />',
            $this->tag('{{ theme:css src="style" tag="true" }}')
        );
    }

    public function testOutputsAssetPath()
    {
        $this->assertEquals(
            $this->path.'/img/hat.jpg',
            $this->tag('{{ theme:asset src="img/hat.jpg" }}')
        );
    }

    public function testOutputsAssetPathAndDoesntAppendExtension()
    {
        $this->assertEquals(
            $this->path.'/img/hat',
            $this->tag('{{ theme:asset src="img/hat" }}')
        );
    }

    public function testOutputsAssetPathDynamically()
    {
        $this->assertEquals(
            $this->path.'/img/hat.jpg',
            $this->tag('{{ theme:img src="hat.jpg" }}')
        );
    }

    public function testOutputsFileContents()
    {
        $contents = File::get('site/themes/redwood/package.json');

        $this->assertEquals(
            $contents,
            $this->tag('{{ theme:output src="package.json" }}')
        );
    }

    public function testAppendsTimestampForCacheBusting()
    {
        File::shouldReceive('lastModified')
            ->with(public_path('/js/foo.js'))
            ->andReturn('12345');

        $this->assertEquals(
            '/js/foo.js?v=12345',
            $this->tag('{{ theme:js src="foo" cache_bust="true" }}')
        );
    }

    /** @test */
    function gets_versioned_filename_for_mix()
    {
        File::shouldReceive('get')
            ->with(public_path('mix-manifest.json'))
            ->andReturn('{"/js/foo.js": "/js/foo.js?id=12345"}');

        $this->assertEquals(
            '/js/foo.js?id=12345',
            $this->tag('{{ theme:js src="foo" version="true" }}')
        );
    }

    /** @test */
    public function gets_versioned_filename_for_elixir()
    {
        File::shouldReceive('get')
            ->with(public_path('mix-manifest.json'))
            ->andReturnNull();

        File::shouldReceive('get')
            ->with(public_path('build/rev-manifest.json'))
            ->andReturn('{"js/foo.js": "js/foo-12345.js"}');

        $this->assertEquals(
            '/build/js/foo-12345.js',
            $this->tag('{{ theme:js src="foo" version="true" }}')
        );
    }

    /** @test */
    function gets_regular_filename_if_file_isnt_in_mix_manifest()
    {
        File::shouldReceive('get')
            ->with(public_path('mix-manifest.json'))
            ->andReturn('{"/js/foo.js": "/js/foo.js?id=12345"}');

        $this->assertEquals(
            '/js/non-versioned-file.js',
            $this->tag('{{ theme:js src="non-versioned-file" version="true" }}')
        );
    }

    /** @test */
    function gets_regular_filename_if_file_isnt_in_elixir_manifest()
    {
        File::shouldReceive('get')
            ->with(public_path('mix-manifest.json'))
            ->andReturnNull();

        File::shouldReceive('get')
            ->with(public_path('build/rev-manifest.json'))
            ->andReturn('{"js/foo.js": "js/foo-12345.js"}');

        $this->assertEquals(
            '/js/non-versioned-file.js',
            $this->tag('{{ theme:js src="non-versioned-file" version="true" }}')
        );
    }

    /** @test */
    function gets_regular_filename_if_manifests_dont_exist()
    {
        File::shouldReceive('get')
            ->with(public_path('mix-manifest.json'))
            ->andReturnNull();

        File::shouldReceive('get')
            ->with(public_path('build/rev-manifest.json'))
            ->andReturnNull();

        $this->assertEquals(
            '/js/foo.js',
            $this->tag('{{ theme:js src="foo" version="true" }}')
        );
    }
}
