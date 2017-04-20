<?php
/**
 * Test that the module is configured correctly
 *
 * @category Mage
 * @package  Oyst_Oyst
 * @author   Oyst Team <dev@oyst.com>
 */
class Oyst_Oyst_Test_Config_ConfigTest extends EcomDev_PHPUnit_Test_Case_Config
{
    /**
     * Ensure the module is in the right code pool
     */
    public function testShouldBeInCommunityPool()
    {
        $this->assertModuleCodePool('community');
    }
}
