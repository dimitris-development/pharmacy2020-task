<?php /** @noinspection PhpMissingStrictTypesDeclarationInspection */

use Laravel\Lumen\Application;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;

/**
 * Class TestCase
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return Application
     * @noinspection PhpMethodNamingConventionInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection UsingInclusionReturnValueInspection
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
