<?php

namespace Tests\MapasBlame;

use Laminas\Diactoros\ServerRequest;
use MapasBlame\Controller;
use MapasBlame\Entities\Blame;
use Tests\Abstract\TestCase;
use Tests\Traits\UserDirector;

class ControllerApiTest extends TestCase
{
    use UserDirector;

    // Nota: "$app->controller('blame') resolve para MapasBlame\Controller" já está coberto
    // por SmokeTest::testBlameControllerIsRegistered — não duplicado aqui.

    function testConstructorDoesNotThrow()
    {
        $controller = new Controller();

        $this->assertInstanceOf(Controller::class, $controller);
    }

    function testEntityClassNameIsExplicit()
    {
        $controller = new Controller();

        $this->assertSame('MapasBlame\Entities\Blame', $controller->entityClassName);
    }

    function testUsesApiReturnsTrue()
    {
        $this->assertTrue(Controller::usesAPI());
    }

    /**
     * Blame.php:9-11 declara TANTO `@ORM\Entity(readOnly=true)` QUANTO
     * `@ORM\entity(repositoryClass=...)` (mesma classe, diferindo só no case — PHP resolve
     * nomes de classe sem diferenciar maiúsculas/minúsculas) — a segunda anotação (sem
     * readOnly) sobrescreve a primeira, e `readOnly=true` é perdido silenciosamente na
     * metadata real do Doctrine. É a ÚNICA entidade no codebase combinando os dois desse
     * jeito — comportamento atual observado, não a intenção do código.
     */
    function testBlameEntityReadOnlyAnnotationIsSilentlyOverriddenByRepositoryClassAnnotation()
    {
        $metadata = $this->app->em->getClassMetadata(Blame::class);

        $this->assertFalse($metadata->isReadOnly);
        $this->assertSame('blame', $metadata->getTableName());
    }

    /**
     * Cobre também o cenário "API blame.<<*>> como admin → não é barrada pelo gate 403"
     * (Plugin.php:132-135) — o mesmo hit satisfaz as duas asserções.
     */
    function testApiFindAsAdminRespondsWithArray()
    {
        $admin = $this->userDirector->createUser('admin');
        $this->login($admin);

        $request = new ServerRequest(method: 'GET', uri: '/api/blame/find');

        $this->app->reset();
        $this->app->run($request, false);

        $this->assertSame(200, $this->app->response->getStatusCode());

        $body = json_decode((string) $this->app->response->getBody(), true);
        $this->assertIsArray($body);
    }
}
