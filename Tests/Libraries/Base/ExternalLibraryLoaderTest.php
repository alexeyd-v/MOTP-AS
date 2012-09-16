<?php

class Base_ExternalLibraryLoader_Test extends PHPUnit_Framework_TestCase
{
    public function testCreateObject()
    {
        $request = new Base_ExternalLibraryLoader();
        $this->assertTrue(is_object($request));
        $this->assertTrue(get_class($request) == 'Base_ExternalLibraryLoader');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadingNoLibrary()
    {
        @Base_ExternalLibraryLoader::findLibrary(null);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadingEmptyLibrary()
    {
        @Base_ExternalLibraryLoader::findLibrary('');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadingValidYetNonExistantLibrary()
    {
        @Base_ExternalLibraryLoader::findLibrary('Dummy');
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testLoadingValidLibraryByNotYetInstalled()
    {
        @Base_ExternalLibraryLoader::findLibrary('.UnitTest1');
    }
    
    public function testLoadingValidLibraryWithOneVersion()
    {
        $this->assertTrue(Base_ExternalLibraryLoader::findLibrary('.UnitTest2') == 'current');
    }
    
    public function testLoadingValidLibraryWithTwoVersions()
    {
        $this->assertTrue(Base_ExternalLibraryLoader::findLibrary('.UnitTest3') == '0.2');
    }
}